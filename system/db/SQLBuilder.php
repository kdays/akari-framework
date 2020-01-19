<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-03
 * Time: 13:45
 */

namespace Akari\system\db;

use PDO;
use Akari\exception\DBException;
use Akari\system\util\AkariDebugUtil;

class SQLBuilder {

    protected $connection;
    protected $tablePrefix = '';
    protected $guid = 0;
    protected $statement = NULL;
    protected $logs = [];
    protected $queryWrite = FALSE;
    protected $inAction = FALSE;
    public $debug = FALSE;

    public function setIsReadQuery($isReadQuery) {
        $this->queryWrite = !$isReadQuery;
    }

    public function getConnection() {
        if ($this->queryWrite || $this->inAction) {
            return $this->connection->getWriteConnection();
        }

        return $this->connection->getReadConnection();
    }

    public function __construct(DBConnection $connection) {
        $this->connection = $connection;
    }

    public function mapKey() {
        return ':AKARI_' . $this->guid++ . "_AKARI";
    }

    protected function tableQuote(string $table) {
        if ($this->connection->getDbType() == 'mysql') {
            return '`' . $this->tablePrefix . $table . '`';
        }

        return '"' . $this->tablePrefix . $table . '"';
    }

    public function quote($string) {
        return $this->getConnection()->quote($string);
    }

    protected function typeMap($value, string $type) {
        $map = [
            'NULL' => PDO::PARAM_NULL,
            'integer' => PDO::PARAM_INT,
            'double' => PDO::PARAM_STR,
            'boolean' => PDO::PARAM_BOOL,
            'string' => PDO::PARAM_STR,
            'object' => PDO::PARAM_STR,
            'resource' => PDO::PARAM_LOB
        ];

        if ($type === 'boolean')  {
            $value = ($value ? '1' : '0');
        } elseif ($type === 'NULL') {
            $value = NULL;
        }

        return [$value, $map[ $type ]];
    }

    protected function columnQuote(string $string) {
        $dot = $this->connection->getDbType() == 'mysql' ? '`': '"';

        if (strpos($string, '.') !== FALSE)  {
            return $dot . $this->tablePrefix . str_replace('.', '"."', $string) . $dot;
        }

        return $dot . $string . $dot;
    }

    protected function columnPush(&$columns, &$map) {
        if ($columns === '*')  {
            return $columns;
        }

        $stack = [];

        if (is_string($columns)) {
            $columns = [$columns];
        }

        foreach ($columns as $key => $value) {
            if (is_array($value)) {
                $stack[] = $this->columnPush($value, $map);
            } elseif (!is_int($key) && $raw = $this->buildRaw($value, $map)) {
                preg_match('/(?<column>[a-zA-Z0-9_\.]+)(\s*\[(?<type>(String|Bool|Int|Number))\])?/i', $key, $match);

                $stack[] = $raw . ' AS ' . $this->columnQuote( $match[ 'column' ] );
            } elseif (is_int($key) && is_string($value)) {
                preg_match('/(?<column>[a-zA-Z0-9_\.]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/i', $value, $match);

                if (!empty($match[ 'alias' ])) {
                    $stack[] = $this->columnQuote( $match[ 'column' ] ) . ' AS ' . $this->columnQuote( $match[ 'alias' ] );

                    $columns[ $key ] = $match[ 'alias' ];

                    if (!empty($match[ 'type' ]))
                    {
                        $columns[ $key ] .= ' [' . $match[ 'type' ] . ']';
                    }
                } else {
                    $stack[] = $this->columnQuote( $match[ 'column' ] );
                }
            }
        }

        return implode($stack, ',');
    }

    protected function arrayQuote(array $array) {
        $stack = [];

        foreach ($array as $value) {
            $stack[] = is_int($value) ? $value : $this->getConnection()->quote($value);
        }

        return implode($stack, ',');
    }

    protected function innerConjunct(array $data, $map, $conjunctor, $outer_conjunctor) {
        $stack = [];

        foreach ($data as $value) {
            $stack[] = '(' . $this->dataImplode($value, $map, $conjunctor) . ')';
        }

        return implode($outer_conjunctor . ' ', $stack);
    }

    protected function dataImplode(array $data, &$map, $conjunctor) {
        $stack = [];

        foreach ($data as $key => $value) {
            $type = gettype($value);

            if (
                $type === 'array' &&
                preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relation_match)
            ) {
                $relationship = $relation_match[ 1 ];

                $stack[] = $value !== array_keys(array_keys($value)) ?
                    '(' . $this->dataImplode($value, $map, ' ' . $relationship) . ')' :
                    '(' . $this->innerConjunct($value, $map, ' ' . $relationship, $conjunctor) . ')';

                continue;
            }

            $map_key = $this->mapKey();

            if (
                is_int($key) &&
                preg_match('/([a-zA-Z0-9_\.]+)\[(?<operator>\>\=?|\<\=?|\!?\=)\]([a-zA-Z0-9_\.]+)/i', $value, $match)
            ) {
                $stack[] = $this->columnQuote($match[ 1 ]) . ' ' . $match[ 'operator' ] . ' ' . $this->columnQuote($match[ 3 ]);
            } else {
                preg_match('/([a-zA-Z0-9_\.]+)(\[(?<operator>\>\=?|\<\=?|\!|\<\>|\>\<|\!?~|REGEXP)\])?/i', $key, $match);
                $column = $this->columnQuote($match[ 1 ]);

                if (isset($match[ 'operator' ])) {
                    $operator = $match[ 'operator' ];

                    if (in_array($operator, ['>', '>=', '<', '<='])) {
                        $condition = $column . ' ' . $operator . ' ';

                        if (is_numeric($value)) {
                            $condition .= $map_key;
                            $map[ $map_key ] = [$value, PDO::PARAM_INT];
                        } elseif ($raw = $this->buildRaw($value, $map)) {
                            $condition .= $raw;
                        } else {
                            $condition .= $map_key;
                            $map[ $map_key ] = [$value, PDO::PARAM_STR];
                        }

                        $stack[] = $condition;
                    } elseif ($operator === '!') {
                        switch ($type)
                        {
                            case 'NULL':
                                $stack[] = $column . ' IS NOT NULL';
                                break;

                            case 'array':
                                $placeholders = [];

                                foreach ($value as $index => $item) {
                                    $placeholders[] = $map_key . $index . '_i';
                                    $map[ $map_key . $index . '_i' ] = $this->typeMap($item, gettype($item));
                                }

                                $stack[] = $column . ' NOT IN (' . implode(', ', $placeholders) . ')';
                                break;

                            case 'object':
                                if ($raw = $this->buildRaw($value, $map)) {
                                    $stack[] = $column . ' != ' . $raw;
                                }
                                break;

                            case 'integer':
                            case 'double':
                            case 'boolean':
                            case 'string':
                                $stack[] = $column . ' != ' . $map_key;
                                $map[ $map_key ] = $this->typeMap($value, $type);
                                break;
                        }
                    } elseif ($operator === '~' || $operator === '!~') {
                        if ($type !== 'array') {
                            $value = [ $value ];
                        }

                        $connector = ' OR ';
                        $data = array_values($value);

                        if (is_array($data[ 0 ])) {
                            if (isset($value[ 'AND' ]) || isset($value[ 'OR' ])) {
                                $connector = ' ' . array_keys($value)[ 0 ] . ' ';
                                $value = $data[ 0 ];
                            }
                        }

                        $like_clauses = [];

                        foreach ($value as $index => $item) {
                            $item = strval($item);

                            if (!preg_match('/(\[.+\]|_|%.+|.+%)/', $item)) {
                                $item = '%' . $item . '%';
                            }

                            $like_clauses[] = $column . ($operator === '!~' ? ' NOT' : '') . ' LIKE ' . $map_key . 'L' . $index;
                            $map[ $map_key . 'L' . $index ] = [$item, PDO::PARAM_STR];
                        }

                        $stack[] = '(' . implode($connector, $like_clauses) . ')';
                    } elseif ($operator === '<>' || $operator === '><') {
                        if ($type === 'array') {
                            if ($operator === '><') {
                                $column .= ' NOT';
                            }

                            $stack[] = '(' . $column . ' BETWEEN ' . $map_key . 'a AND ' . $map_key . 'b)';

                            $data_type = (is_numeric($value[ 0 ]) && is_numeric($value[ 1 ])) ? PDO::PARAM_INT : PDO::PARAM_STR;

                            $map[ $map_key . 'a' ] = [$value[ 0 ], $data_type];
                            $map[ $map_key . 'b' ] = [$value[ 1 ], $data_type];
                        }
                    } elseif ($operator === 'REGEXP') {
                        $stack[] = $column . ' REGEXP ' . $map_key;
                        $map[ $map_key ] = [$value, PDO::PARAM_STR];
                    }
                } else {
                    switch ($type) {
                        case 'NULL':
                            $stack[] = $column . ' IS NULL';
                            break;

                        case 'array':
                            $placeholders = [];

                            foreach ($value as $index => $item) {
                                $placeholders[] = $map_key . $index . '_i';
                                $map[ $map_key . $index . '_i' ] = $this->typeMap($item, gettype($item));
                            }

                            $stack[] = $column . ' IN (' . implode(', ', $placeholders) . ')';
                            break;

                        case 'object':
                            if ($raw = $this->buildRaw($value, $map)) {
                                $stack[] = $column . ' = ' . $raw;
                            }
                            break;

                        case 'integer':
                        case 'double':
                        case 'boolean':
                        case 'string':
                            $stack[] = $column . ' = ' . $map_key;
                            $map[ $map_key ] = $this->typeMap($value, $type);
                            break;
                    }
                }
            }
        }

        return implode($conjunctor . ' ', $stack);
    }

    protected function whereClause($where, &$map) {
        $where_clause = '';

        if (is_array($where)) {
            /// conditions我们单独处理 如果有conditions时代表直接处理
            if (array_key_exists("conditions", $where)) {
                $conditions = $where['conditions'];
            } else {
                $conditions = array_diff_key($where, array_flip(
                    ['GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH']
                ));
            }

            if (!empty($conditions)) {
                $where_clause = ' WHERE ' . $this->dataImplode($conditions, $map, ' AND');
            }

            $opMatch = $where['MATCH'] ?? $where['match'] ?? NULL;
            if (!empty($opMatch) && $this->connection->getDbType() == 'mysql') {
                if (is_array($opMatch) && isset($opMatch['columns'], $opMatch['keyword'])) {
                    $mode = '';

                    $mode_array = [
                        'natural' => 'IN NATURAL LANGUAGE MODE',
                        'natural+query' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION',
                        'boolean' => 'IN BOOLEAN MODE',
                        'query' => 'WITH QUERY EXPANSION'
                    ];

                    if (isset($opMatch[ 'mode' ], $mode_array[ $opMatch[ 'mode' ] ])) {
                        $mode = ' ' . $mode_array[ $opMatch[ 'mode' ] ];
                    }

                    $columns = implode(array_map([$this, 'columnQuote'], $opMatch[ 'columns' ]), ', ');
                    $map_key = $this->mapKey();
                    $map[ $map_key ] = [$opMatch[ 'keyword' ], PDO::PARAM_STR];

                    $where_clause .= ($where_clause !== '' ? ' AND ' : ' WHERE') . ' MATCH (' . $columns . ') AGAINST (' . $map_key . $mode . ')';
                }
            }

            $opGroup = $where['GROUP'] ?? $where['group'] ?? NULL;
            if (!empty($opGroup)) {
                if (is_array($opGroup)) {
                    $stack = [];

                    foreach ($opGroup as $column => $value) {
                        $stack[] = $this->columnQuote($value);
                    }

                    $where_clause .= ' GROUP BY ' . implode($stack, ',');
                } elseif ($raw = $this->buildRaw($opGroup, $map)) {
                    $where_clause .= ' GROUP BY ' . $raw;
                } else {
                    $where_clause .= ' GROUP BY ' . $this->columnQuote($opGroup);
                }

                $opHaving = $where['HAVING'] ?? $where['having'] ?? NULL;
                if (!empty($opHaving)) {
                    if ($raw = $this->buildRaw($opHaving, $map)) {
                        $where_clause .= ' HAVING ' . $raw;
                    } else {
                        $where_clause .= ' HAVING ' . $this->dataImplode($opHaving, $map, ' AND');
                    }
                }
            }

            $opOrder = $where['ORDER'] ?? $where['order'] ?? NULL;
            $opLimit = $where['LIMIT'] ?? $where['limit'] ?? NULL;
            $opSkip = $where['SKIP'] ?? $where['skip'] ?? NULL;

            if (!empty($opSkip)) {
                // 这里我们先检查opLimit的拼接
                if (!empty($opLimit)) {
                    $opLimit = is_numeric($opLimit) ? [$opSkip, $opLimit] : [$opSkip, $opLimit[1]];
                } else {
                    $opLimit = [$opSkip, $opLimit];
                }
            }

            if (!empty($opOrder)) {
                if (is_array($opOrder)) {
                    $stack = [];
                    $specOrderMap = ['-1' => 'DESC', '1' => 'ASC'];

                    foreach ($opOrder as $column => $value) {
                        if (is_array($value)) {
                            $stack[] = 'FIELD(' . $this->columnQuote($column) . ', ' . $this->arrayQuote($value) . ')';
                        } elseif ($value === 'ASC' || $value === 'DESC' || isset($specOrderMap[$value])) {
                            $stack[] = $this->columnQuote($column) . ' ' . ($specOrderMap[$value] ?? $value);
                        } elseif (is_int($column)) {
                            $stack[] = $this->columnQuote($value);
                        }
                    }

                    $where_clause .= ' ORDER BY ' . implode($stack, ',');
                } elseif ($raw = $this->buildRaw($opOrder, $map)) {
                    $where_clause .= ' ORDER BY ' . $raw;
                } else {
                    $where_clause .= ' ORDER BY ' . $this->columnQuote($opOrder);
                }

                if(!empty($opLimit) && in_array($this->connection->getDbType(), ['oracle', 'mssql'])) {
                    if (is_numeric($opLimit)) {
                        $opLimit = [0, $opLimit];
                    }

                    if (is_array($opLimit) && is_numeric($opLimit[ 0 ]) && is_numeric($opLimit[ 1 ])) {
                        $where_clause .= ' OFFSET ' . $opLimit[ 0 ] . ' ROWS FETCH NEXT ' . $opLimit[ 1 ] . ' ROWS ONLY';
                    }
                }
            }

            // mysql可以在非order时直接LIMIT
            if (!empty($opLimit) && !in_array($this->connection->getDbType(), ['mssql', 'oracle'])) {
                if (is_numeric($opLimit)) {
                    $where_clause .= ' LIMIT ' . $opLimit;
                } elseif (is_array($opLimit) && is_numeric($opLimit[ 0 ]) && is_numeric($opLimit[ 1 ])) {
                    $where_clause .= ' LIMIT ' . $opLimit[ 1 ] . ' OFFSET ' . $opLimit[ 0 ];
                }
            }
        } elseif ($raw = $this->buildRaw($where, $map)) {
            $where_clause .= ' ' . $raw;
        }

        return $where_clause;
    }

    protected function selectContext(string $table, &$map, $join, &$columns = NULL, $where = NULL, $column_fn = NULL) {
        preg_match('/(?<table>[a-zA-Z0-9_]+)\s*\((?<alias>[a-zA-Z0-9_]+)\)/i', $table, $table_match);

        if (isset($table_match[ 'table' ], $table_match[ 'alias' ])) {
            $table = $this->tableQuote($table_match[ 'table' ]);

            $table_query = $table . ' AS ' . $this->tableQuote($table_match[ 'alias' ]);
        } else {
            $table = $this->tableQuote($table);

            $table_query = $table;
        }

        $join_key = is_array($join) ? array_keys($join) : NULL;

        if (isset($join_key[ 0 ]) && strpos($join_key[ 0 ], '[') === 0) {
            $table_join = [];

            $join_array = [
                '>' => 'LEFT',
                '<' => 'RIGHT',
                '<>' => 'FULL',
                '><' => 'INNER'
            ];

            foreach($join as $sub_table => $relation) {
                preg_match('/(\[(?<join>\<\>?|\>\<?)\])?(?<table>[a-zA-Z0-9_]+)\s?(\((?<alias>[a-zA-Z0-9_]+)\))?/', $sub_table, $match);

                if ($match[ 'join' ] !== '' && $match[ 'table' ] !== '') {
                    if (is_string($relation)) {
                        $relation = 'USING ("' . $relation . '")';
                    }

                    if (is_array($relation)) {
                        // For ['column1', 'column2']
                        if (isset($relation[ 0 ])) {
                            $relation = 'USING ("' . implode($relation, '", "') . '")';
                        } else {
                            $joins = [];

                            foreach ($relation as $key => $value) {
                                $joins[] = (
                                    strpos($key, '.') > 0 ?
                                        // For ['tableB.column' => 'column']
                                        $this->columnQuote($key) :

                                        // For ['column1' => 'column2']
                                        $table . '."' . $key . '"'
                                    ) .
                                    ' = ' .
                                    $this->tableQuote(isset($match[ 'alias' ]) ? $match[ 'alias' ] : $match[ 'table' ]) . '."' . $value . '"';
                            }

                            $relation = 'ON ' . implode($joins, ' AND ');
                        }
                    }

                    $table_name = $this->tableQuote($match[ 'table' ]) . ' ';

                    if (isset($match[ 'alias' ])) {
                        $table_name .= 'AS ' . $this->tableQuote($match[ 'alias' ]) . ' ';
                    }

                    $table_join[] = $join_array[ $match[ 'join' ] ] . ' JOIN ' . $table_name . $relation;
                }
            }

            $table_query .= ' ' . implode($table_join, ' ');
        } else {
            if (is_null($columns)) {
                if (!is_null($where) || (is_array($join) && isset($column_fn))) {
                    $where = $join;
                    $columns = NULL;
                } else {
                    $where = NULL;
                    $columns = $join;
                }
            } else {
                $where = $columns;
                $columns = $join;
            }
        }

        if (isset($column_fn)) {
            if ($column_fn === 1) {
                $column = '1';

                if (is_null($where)) {
                    $where = $columns;
                }
            } elseif ($raw = $this->buildRaw($column_fn, $map)) {
                $column = $raw;
            } else {
                if (empty($columns) || $columns instanceof Raw) {
                    $columns = '*';
                    $where = $join;
                }

                $column = $column_fn . '(' . $this->columnPush($columns, $map) . ')';
            }
        } else {
            $column = $this->columnPush($columns, $map);
        }

        return 'SELECT ' . $column . ' FROM ' . $table_query . $this->whereClause($where, $map);
    }

    protected function columnMap($columns, array &$stack) {
        if ($columns === '*') {
            return $stack;
        }

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                preg_match('/([a-zA-Z0-9_]+\.)?(?<column>[a-zA-Z0-9_]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\))?(?:\s*\[(?<type>(?:String|Bool|Int|Number|Object|JSON))\])?/i', $value, $key_match);

                $column_key = !empty($key_match[ 'alias' ]) ?
                    $key_match[ 'alias' ] :
                    $key_match[ 'column' ];

                if (isset($key_match[ 'type' ])) {
                    $stack[ $value ] = [$column_key, $key_match[ 'type' ]];
                } else {
                    $stack[ $value ] = [$column_key, 'String'];
                }
            } elseif ($value instanceof Raw) {
                preg_match('/([a-zA-Z0-9_]+\.)?(?<column>[a-zA-Z0-9_]+)(\s*\[(?<type>(String|Bool|Int|Number))\])?/i', $key, $key_match);

                $column_key = $key_match[ 'column' ];

                if (isset($key_match[ 'type' ])) {
                    $stack[ $key ] = [$column_key, $key_match[ 'type' ]];
                } else {
                    $stack[ $key ] = [$column_key, 'String'];
                }
            } elseif (!is_int($key) && is_array($value)) {
                $this->columnMap($value, $stack);
            }
        }

        return $stack;
    }

    protected function dataMap(array $data, array $columns, array $column_map, array &$stack) {
        foreach ($columns as $key => $value) {
            $isRaw = $value instanceof Raw;
            if (is_int($key) || $isRaw) {
                $map = $column_map[ $isRaw  ? $key : $value ];
                $column_key = $map[ 0 ];
                $result = $data[ $column_key ];

                if (isset($map[ 1 ])) {
                    if ($isRaw && in_array($map[ 1 ], ['Object', 'JSON'])) {
                        continue;
                    }

                    if (is_null($result)) {
                        $stack[ $column_key ] = NULL;
                        continue;
                    }

                    switch ($map[ 1 ]) {
                        case 'Number':
                            $stack[ $column_key ] = (float) $result;
                            break;

                        case 'Int':
                            $stack[ $column_key ] = (int) $result;
                            break;

                        case 'Bool':
                            $stack[ $column_key ] = (bool) $result;
                            break;

                        case 'Object':
                            $stack[ $column_key ] = unserialize($result);
                            break;

                        case 'JSON':
                            $stack[ $column_key ] = json_decode($result, TRUE);
                            break;

                        case 'String':
                            $stack[ $column_key ] = $result;
                            break;
                    }
                } else {
                    $stack[ $column_key ] = $result;
                }
            } else {
                $current_stack = [];
                $this->dataMap($data, $value, $column_map, $current_stack);

                $stack[ $key ] = $current_stack;
            }
        }
    }

    public function exec($query, array $map) {
        $this->logs[] = [$query, $map];
        $st = $this->getConnection()->prepare($query);
        if ($this->debug) {
            var_dump($this->generate($query, $map));
            var_dump($query, $map);die;
        }

        AkariDebugUtil::pushSqlBuilder($this, $query, $map);

        if ($st) {
            foreach ($map as $key => $value) {
                $st->bindValue($key, $value[0], $value[1]);
            }

            if (!$st->execute()) {
                $queryString = $this->generate($query, $map);

                $ex = new DBException(implode(" ", $st->errorInfo()), $st->errorCode());
                $ex->setQueryString($queryString);

                throw $ex;
            }
            $this->statement = $st;

            return $this->statement;
        }

        return FALSE;
    }

    public function generate($query, array $map) {
        $identifier = [
            'mysql' => '`$1`',
            'mssql' => '[$1]'
        ];

        $dbType = $this->connection->getDbType();
        $query = preg_replace(
            '/"([a-zA-Z0-9_]+)"/i',
            isset($identifier[$dbType ]) ?  $identifier[ $dbType] : '"$1"',
            $query
        );

        foreach ($map as $key => $value) {
            if ($value[ 1 ] === PDO::PARAM_STR) {
                $replace = $this->quote($value[ 0 ]);
            } elseif ($value[ 1 ] === PDO::PARAM_NULL) {
                $replace = 'NULL';
            } elseif ($value[ 1 ] === PDO::PARAM_LOB) {
                $replace = '{LOB_DATA}';
            } else {
                $replace = $value[ 0 ];
            }

            $query = str_replace($key, $replace, $query);
        }

        return $query;
    }

    public static function raw(string $value, array $map = []) {
        $raw = new Raw();

        $raw->value = $value;
        $raw->map = $map;

        return $raw;
    }

    protected function buildRaw($raw, &$map) {
        if (!($raw instanceof Raw)) {
            return FALSE;
        }

        $query = preg_replace_callback(
            '/((FROM|TABLE|INTO|UPDATE)\s*)?\<([a-zA-Z0-9_\.]+)\>/i',
            function ($matches) {
                if (!empty($matches[ 2 ])) {
                    return $matches[ 2 ] . ' ' . $this->tableQuote($matches[ 3 ]);
                }

                return $this->Quote($matches[ 3 ]);
            },
            $raw->value);

        $raw_map = $raw->map;

        if (!empty($raw_map)) {
            foreach ($raw_map as $key => $value) {
                $map[ $key ] = $this->typeMap($value, gettype($value));
            }
        }

        return $query;
    }

    public function action(callable $action) {
        $this->inAction = TRUE;
        $this->getConnection()->beginTransaction();
        $result = NULL;

        try {
            if ($result = $action($this)) {
                $this->getConnection()->commit();
            } else {
                $this->getConnection()->rollBack();
            }
        } catch (\Exception $e) {
            $this->getConnection()->rollBack();
            throw $e;
        } finally {
            $this->inAction = FALSE;
        }

        return $result;
    }


    ////// 执行相关操作
    public function select($table, $join, $columns = NULL, $where = NULL) {
        $this->setIsReadQuery(TRUE);

        $map = [];
        $stack = [];
        $column_map = [];

        $index = 0;
        $column = $where === NULL ? $join : $columns;

        $is_single = (is_string($column) && $column !== '*');
        $query = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);

        $this->columnMap($columns, $column_map);

        if (!$query) {
            return FALSE;
        }

        if ($columns === '*') {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($is_single) {
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }

        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $current_stack = [];
            $this->dataMap($data, $columns, $column_map, $current_stack);

            $stack[ $index ] = $current_stack;

            $index++;
        }

        return $stack;
    }

    public function insert($table, $datas) {
        $this->setIsReadQuery(FALSE);

        $stack = [];
        $columns = [];
        $fields = [];
        $map = [];

        if (!isset($datas[ 0 ])) {
            $datas = [$datas];
        }

        foreach ($datas as $data) {
            foreach ($data as $key => $value) {
                $columns[] = $key;
            }
        }

        $columns = array_unique($columns);

        foreach ($datas as $data) {
            $values = [];

            foreach ($columns as $key) {
                if ($raw = $this->buildRaw($data[ $key ], $map)) {
                    $values[] = $raw;
                    continue;
                }

                $map_key =$this->mapKey();

                $values[] = $map_key;

                if (!isset($data[ $key ])) {
                    $map[ $map_key ] = [NULL, PDO::PARAM_NULL];
                } else {
                    $value = $data[ $key ];

                    $type = gettype($value);

                    switch ($type) {
                        case 'array':
                            $map[ $map_key ] = [
                                strpos($key, '[JSON]') === strlen($key) - 6 ?
                                    json_encode($value) :
                                    serialize($value),
                                PDO::PARAM_STR
                            ];
                            break;

                        case 'object':
                            $value = serialize($value);

                        case 'NULL':
                        case 'resource':
                        case 'boolean':
                        case 'integer':
                        case 'double':
                        case 'string':
                            $map[ $map_key ] = $this->typeMap($value, $type);
                            break;
                    }
                }
            }

            $stack[] = '(' . implode($values, ', ') . ')';
        }

        foreach ($columns as $key) {
            $fields[] = $this->columnQuote(preg_replace("/(\s*\[JSON\]$)/i", '', $key));
        }

        return $this->exec('INSERT INTO ' . $this->tableQuote($table) . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $stack), $map);
    }

    public function update($table, $data, $where = NULL) {
        $this->setIsReadQuery(FALSE);
        $fields = [];
        $map = [];

        foreach ($data as $key => $value) {
            $column = $this->columnQuote(preg_replace("/(\s*\[(JSON|\+|\-|\*|\/)\]$)/i", '', $key));

            if ($raw = $this->buildRaw($value, $map)) {
                $fields[] = $column . ' = ' . $raw;
                continue;
            }

            $map_key = $this->mapKey();

            preg_match('/(?<column>[a-zA-Z0-9_]+)(\[(?<operator>\+|\-|\*|\/)\])?/i', $key, $match);

            if (isset($match[ 'operator' ])) {
                if (is_numeric($value))
                {
                    $fields[] = $column . ' = ' . $column . ' ' . $match[ 'operator' ] . ' ' . $value;
                }
            } else {
                $fields[] = $column . ' = ' . $map_key;

                $type = gettype($value);

                switch ($type) {
                    case 'array':
                        $map[ $map_key ] = [
                            strpos($key, '[JSON]') === strlen($key) - 6 ?
                                json_encode($value) :
                                serialize($value),
                            PDO::PARAM_STR
                        ];
                        break;

                    case 'object':
                        $value = serialize($value);

                    case 'NULL':
                    case 'resource':
                    case 'boolean':
                    case 'integer':
                    case 'double':
                    case 'string':
                        $map[ $map_key ] = $this->typeMap($value, $type);
                        break;
                }
            }
        }

        return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $fields) . $this->whereClause($where, $map), $map);
    }

    public function delete($table, $where) {
        $this->setIsReadQuery(FALSE);
        $map = [];

        return $this->exec('DELETE FROM ' . $this->tableQuote($table) . $this->whereClause($where, $map), $map);
    }


    public function replace($table, $columns, $where = NULL) {
        $this->setIsReadQuery(FALSE);

        if (!is_array($columns) || empty($columns)) {
            return FALSE;
        }

        $map = [];
        $stack = [];

        foreach ($columns as $column => $replacements) {
            if (is_array($replacements)) {
                foreach ($replacements as $old => $new) {
                    $map_key = $this->mapKey();

                    $stack[] = $this->columnQuote($column) . ' = REPLACE(' . $this->columnQuote($column) . ', ' . $map_key . 'a, ' . $map_key . 'b)';

                    $map[ $map_key . 'a' ] = [$old, PDO::PARAM_STR];
                    $map[ $map_key . 'b' ] = [$new, PDO::PARAM_STR];
                }
            }
        }

        if (!empty($stack)) {
            return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $stack) . $this->whereClause($where, $map), $map);
        }

        return FALSE;
    }

    public function get($table, $join = NULL, $columns = NULL, $where = NULL) {
        $this->setIsReadQuery(TRUE);

        $map = [];
        $stack = [];
        $column_map = [];

        if ($where === NULL) {
            $column = $join;
            unset($columns[ 'LIMIT' ]);
        } else {
            $column = $columns;
            unset($where[ 'LIMIT' ]);
        }

        $is_single = (is_string($column) && $column !== '*');

        $query = $this->exec($this->selectContext($table, $map, $join, $columns, $where) . ' LIMIT 1', $map);

        if ($query) {
            $data = $query->fetchAll(PDO::FETCH_ASSOC);

            if (isset($data[ 0 ])) {
                if ($column === '*') {
                    return $data[ 0 ];
                }

                $this->columnMap($columns, $column_map);

                $this->dataMap($data[ 0 ], $columns, $column_map, $stack);

                if ($is_single) {
                    return $stack[ $column_map[ $column ][ 0 ] ];
                }

                return $stack;
            }
        }
    }

    private function aggregate($type, $table, $join = NULL, $column = NULL, $where = NULL) {
        $this->setIsReadQuery(TRUE);
        $map = [];
        $query = $this->exec($this->selectContext($table, $map, $join, $column, $where, strtoupper($type)), $map);

        if ($query) {
            $number = $query->fetchColumn();

            return is_numeric($number) ? $number + 0 : $number;
        }

        return FALSE;
    }

    public function count($table, $join = NULL, $column = NULL, $where = NULL) {
        return $this->aggregate('count', $table, $join, $column, $where);
    }

    public function avg($table, $join, $column = NULL, $where = NULL) {
        return $this->aggregate('avg', $table, $join, $column, $where);
    }

    public function max($table, $join, $column = NULL, $where = NULL) {
        return $this->aggregate('max', $table, $join, $column, $where);
    }

    public function min($table, $join, $column = NULL, $where = NULL) {
        return $this->aggregate('min', $table, $join, $column, $where);
    }

    public function sum($table, $join, $column = NULL, $where = NULL) {
        return $this->aggregate('sum', $table, $join, $column, $where);
    }
    /// ///// END

    public function id() {
        $type = $this->connection->getDbType();

        if ($type == 'pgsql') {
            return $this->getConnection()->query("SELECT LASTVAL()")->fetchColumn();
        }

        return $this->getConnection()->lastInsertId();
    }

}

class Raw {

    public $map;

    public $value;

}
