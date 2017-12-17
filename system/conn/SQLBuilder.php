<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/8/24
 * Time: 下午5:06
 */

namespace Akari\system\conn;

/// 如果想要了解原来代码的，请查看https://medoo.in
/// Modify FROM medoo(Author: catfan)

use PDO;

class SQLBuilder {

    const DB_TYPE_MYSQL = 'mysql';
    const DB_TYPE_ORACLE = 'oracle';
    const DB_TYPE_MSSQL = 'mssql';
    const DB_TYPE_SQLITE = 'sqlite';
    const DB_TYPE_PGSQL = 'pgsql';

    protected $database_type;
    protected $connection;

    protected $prefix;
    protected $statement;
    protected $debug_mode = FALSE;
    protected $guid = 0;

    public $isWR = FALSE;

    public function __construct(DBConnection $connection) {
        $this->connection = $connection;
        $readConn = $connection->getReadConnection();

        $this->database_type = $readConn->getAttribute(\PDO::ATTR_DRIVER_NAME);
    }

    public function getConnection() {
        if (!$this->isWR) {
            return $this->connection->getReadConnection();
        }

        return $this->connection->getWriteConnection();
    }

    public function query($query, $map = []) {
        $this->setWRMode(TRUE);

        if (!empty($map)) {
            foreach ($map as $key => $value) {
                switch (gettype($value)) {
                    case 'NULL':
                        $map[$key] = [NULL, PDO::PARAM_NULL];
                        break;
                    case 'resource':
                        $map[$key] = [$value, PDO::PARAM_LOB];
                        break;
                    case 'boolean':
                        $map[$key] = [($value ? '1' : '0'), PDO::PARAM_BOOL];
                        break;
                    case 'integer':
                    case 'double':
                        $map[$key] = [$value, PDO::PARAM_INT];
                        break;
                    case 'string':
                        $map[$key] = [$value, PDO::PARAM_STR];
                        break;
                }
            }
        }

        return $this->exec($query, $map);
    }

    public function exec($query, $map = []) {
        if ($this->debug_mode) {
            echo $this->generate($query, $map);
            $this->debug_mode = FALSE;

            return FALSE;
        }

        $statement = $this->getConnection()->prepare($query);
        if ($statement) {
            foreach ($map as $key => $value) {
                $statement->bindValue($key, $value[0], $value[1]);
            }

            DBUtil::beginBenchmark();

            if (!$statement->execute()) {
                $this->connection->throwErr($statement, $this->generate($query, $map));
            }

            DBUtil::endBenchmark($query);

            $this->statement = $statement;

            return $statement;
        }

        $errorInfo = $this->error();
        throw new DBException("SQL Statement Failed.  [Err] " . $errorInfo[0] . " " . $errorInfo[2]);
    }

    protected function generate($query, $map) {
        krsort($map);
        foreach ($map as $key => $value) {
            if ($value[1] === PDO::PARAM_STR) {
                $query = str_replace($key, $this->quote($value[0]), $query);
            } elseif ($value[1] === PDO::PARAM_NULL) {
                $query = str_replace($key, 'NULL', $query);
            } else {
                $query = str_replace($key, $value[0], $query);
            }
        }

        return $query;
    }

    public function quote($string) {
        return $this->getConnection()->quote($string);
    }

    protected function tableQuote($table) {
        if ($this->database_type == self::DB_TYPE_MYSQL) {
            return '`' . $this->prefix . $table . '`';
        }

        return '"' . $this->prefix . $table . '"';
    }

    protected function mapKey() {
        return ':AB_' . $this->guid++;
    }

    protected function columnQuote($string) {
        preg_match('/(^#)?([a-zA-Z0-9_]*)\.([a-zA-Z0-9_]*)(\s*\[JSON\]$)?/', $string, $column_match);
        if (isset($column_match[2], $column_match[3])) {
            if ($this->database_type == self::DB_TYPE_MYSQL) {
                return '`' . $this->prefix . $column_match[2] . '`.`' . $column_match[3] . '`';
            }

            return '"' . $this->prefix . $column_match[2] . '"."' . $column_match[3] . '"';
        }

        if ($this->database_type == self::DB_TYPE_MYSQL) {
            return '`' . $string . '`';
        }

        return '"' . $string . '"';
    }

    protected function columnPush(&$columns) {
        if ($columns === '*') {
            return $columns;
        }
        $stack = [];
        if (is_string($columns)) {
            $columns = [$columns];
        }
        foreach ($columns as $key => $value) {
            if (is_array($value)) {
                $stack[] = $this->columnPush($value);
            } else {
                preg_match('/(?<column>[a-zA-Z0-9_\.]+)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\)|\s*\[(?<type>(String|Bool|Int|Number|Object|JSON))\])?/i', $value, $match);
                if (!empty($match['alias'])) {
                    $stack[] = $this->columnQuote($match['column']) . ' AS ' . $this->columnQuote($match['alias']);
                    $columns[$key] = $match['alias'];
                } else {
                    $stack[] = $this->columnQuote($match['column']);
                }
            }
        }

        return implode($stack, ',');
    }

    protected function arrayQuote($array) {
        $stack = [];
        foreach ($array as $value) {
            $stack[] = is_int($value) ? $value : $this->getConnection()->quote($value);
        }

        return implode($stack, ',');
    }

    protected function innerConjunct($data, $map, $conjunctor, $outer_conjunctor) {
        $stack = [];
        foreach ($data as $value) {
            $stack[] = '(' . $this->dataImplode($value, $map, $conjunctor) . ')';
        }

        return implode($outer_conjunctor . ' ', $stack);
    }

    protected function fnQuote($column, $string) {
        return (strpos($column, '#') === 0 && preg_match('/^[A-Z0-9\_]*\([^)]*\)$/', $string)) ? $string : $this->quote($string);
    }

    protected function dataImplode($data, &$map, $conjunctor) {
        $wheres = [];
        foreach ($data as $key => $value) {
            $map_key = $this->mapKey();
            $type = gettype($value);
            if (preg_match("/^(AND|OR)(\s+#.*)?$/i", $key, $relation_match) && $type === 'array') {
                $wheres[] = 0 !== count(array_diff_key($value, array_keys(array_keys($value)))) ? '(' . $this->dataImplode($value, $map, ' ' . $relation_match[1]) . ')' : '(' . $this->innerConjunct($value, $map, ' ' . $relation_match[1], $conjunctor) . ')';
            } else {
                if (is_int($key) && preg_match('/([a-zA-Z0-9_\.]+)\[(?<operator>\>|\>\=|\<|\<\=|\!|\=)\]([a-zA-Z0-9_\.]+)/i', $value, $match)) {
                    $wheres[] = $this->columnQuote($match[1]) . ' ' . $match['operator'] . ' ' . $this->columnQuote($match[3]);
                } else {
                    preg_match('/(#?)([a-zA-Z0-9_\.]+)(\[(?<operator>\>|\>\=|\<|\<\=|\!|\<\>|\>\<|\!?~)\])?/i', $key, $match);
                    $column = $this->columnQuote($match[2]);
                    if (!empty($match[1])) {
                        $wheres[] = $column . (isset($match['operator']) ? ' ' . $match['operator'] . ' ' : ' = ') . $this->fnQuote($key, $value);
                        continue;
                    }
                    if (isset($match['operator'])) {
                        $operator = $match['operator'];
                        if ($operator === '!') {
                            switch ($type) {
                                case 'NULL':
                                    $wheres[] = $column . ' IS NOT NULL';
                                    break;
                                case 'array':
                                    $wheres[] = $column . ' NOT IN (' . $this->arrayQuote($value) . ')';
                                    break;
                                case 'integer':
                                case 'double':
                                    $wheres[] = $column . ' != ' . $map_key;
                                    $map[$map_key] = [$value, PDO::PARAM_INT];
                                    break;
                                case 'boolean':
                                    $wheres[] = $column . ' != ' . $map_key;
                                    $map[$map_key] = [($value ? '1' : '0'), PDO::PARAM_BOOL];
                                    break;
                                case 'string':
                                    $wheres[] = $column . ' != ' . $map_key;
                                    $map[$map_key] = [$value, PDO::PARAM_STR];
                                    break;
                            }
                        }
                        if ($operator === '<>' || $operator === '><') {
                            if ($type === 'array') {
                                if ($operator === '><') {
                                    $column .= ' NOT';
                                }
                                $wheres[] = '(' . $column . ' BETWEEN ' . $map_key . 'a AND ' . $map_key . 'b)';
                                $data_type = (is_numeric($value[0]) && is_numeric($value[1])) ? PDO::PARAM_INT : PDO::PARAM_STR;
                                $map[$map_key . 'a'] = [$value[0], $data_type];
                                $map[$map_key . 'b'] = [$value[1], $data_type];
                            }
                        }
                        if ($operator === '~' || $operator === '!~') {
                            if ($type !== 'array') {
                                $value = [$value];
                            }
                            $connector = ' OR ';
                            $stack = array_values($value);
                            if (is_array($stack[0])) {
                                if (isset($value['AND']) || isset($value['OR'])) {
                                    $connector = ' ' . array_keys($value)[0] . ' ';
                                    $value = $stack[0];
                                }
                            }
                            $like_clauses = [];
                            foreach ($value as $index => $item) {
                                $item = strval($item);
                                if (!preg_match('/(\[.+\]|_|%.+|.+%)/', $item)) {
                                    $item = '%' . $item . '%';
                                }
                                $like_clauses[] = $column . ($operator === '!~' ? ' NOT' : '') . ' LIKE ' . $map_key . 'L' . $index;
                                $map[$map_key . 'L' . $index] = [$item, PDO::PARAM_STR];
                            }
                            $wheres[] = '(' . implode($connector, $like_clauses) . ')';
                        }
                        if (in_array($operator, ['>', '>=', '<', '<='])) {
                            $condition = $column . ' ' . $operator . ' ';
                            if (is_numeric($value)) {
                                $condition .= $map_key;
                                $map[$map_key] = [$value, PDO::PARAM_INT];
                            } else {
                                $condition .= $map_key;
                                $map[$map_key] = [$value, PDO::PARAM_STR];
                            }
                            $wheres[] = $condition;
                        }
                    } else {
                        switch ($type) {
                            case 'NULL':
                                $wheres[] = $column . ' IS NULL';
                                break;
                            case 'array':
                                $wheres[] = $column . ' IN (' . $this->arrayQuote($value) . ')';
                                break;
                            case 'integer':
                            case 'double':
                                $wheres[] = $column . ' = ' . $map_key;
                                $map[$map_key] = [$value, PDO::PARAM_INT];
                                break;
                            case 'boolean':
                                $wheres[] = $column . ' = ' . $map_key;
                                $map[$map_key] = [($value ? '1' : '0'), PDO::PARAM_BOOL];
                                break;
                            case 'string':
                                $wheres[] = $column . ' = ' . $map_key;
                                $map[$map_key] = [$value, PDO::PARAM_STR];
                                break;
                        }
                    }
                }
            }
        }

        return implode($conjunctor . ' ', $wheres);
    }

    protected function whereClause($where, &$map) {
        $where_clause = '';
        if (is_array($where)) {
            $where_keys = array_keys($where);
            $where_AND = preg_grep("/^AND\s*#?$/i", $where_keys);
            $where_OR = preg_grep("/^OR\s*#?$/i", $where_keys);
            $single_condition = array_diff_key($where, array_flip(['AND', 'OR', 'GROUP', 'ORDER', 'HAVING', 'LIMIT', 'LIKE', 'MATCH']));
            if (!empty($single_condition)) {
                $condition = $this->dataImplode($single_condition, $map, ' AND');
                if ($condition !== '') {
                    $where_clause = ' WHERE ' . $condition;
                }
            }

            if (!empty($where_AND)) {
                $value = array_values($where_AND);
                $where_clause = ' WHERE ' . $this->dataImplode($where[$value[0]], $map, ' AND');
            }

            if (!empty($where_OR)) {
                $value = array_values($where_OR);
                $where_clause = ' WHERE ' . $this->dataImplode($where[$value[0]], $map, ' OR');
            }

            if (isset($where['MATCH'])) {
                $MATCH = $where['MATCH'];
                if (is_array($MATCH) && isset($MATCH['columns'], $MATCH['keyword'])) {
                    $mode = '';
                    $mode_array = ['natural' => 'IN NATURAL LANGUAGE MODE', 'natural+query' => 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION', 'boolean' => 'IN BOOLEAN MODE', 'query' => 'WITH QUERY EXPANSION'];
                    if (isset($MATCH['mode'], $mode_array[$MATCH['mode']])) {
                        $mode = ' ' . $mode_array[$MATCH['mode']];
                    }
                    $columns = implode(array_map([$this, 'columnQuote'], $MATCH['columns']), ', ');
                    $map_key = $this->mapKey();
                    $map[$map_key] = [$MATCH['keyword'], PDO::PARAM_STR];
                    $where_clause .= ($where_clause !== '' ? ' AND ' : ' WHERE') . ' MATCH (' . $columns . ') AGAINST (' . $map_key . $mode . ')';
                }
            }

            if (isset($where['GROUP'])) {
                $GROUP = $where['GROUP'];
                if (is_array($GROUP)) {
                    $stack = [];
                    foreach ($GROUP as $column => $value) {
                        $stack[] = $this->columnQuote($value);
                    }
                    $where_clause .= ' GROUP BY ' . implode($stack, ',');
                } else {
                    $where_clause .= ' GROUP BY ' . $this->columnQuote($where['GROUP']);
                }
                if (isset($where['HAVING'])) {
                    $where_clause .= ' HAVING ' . $this->dataImplode($where['HAVING'], $map, ' AND');
                }
            }

            if (isset($where['ORDER'])) {
                $ORDER = $where['ORDER'];
                if (is_array($ORDER)) {
                    $stack = [];
                    foreach ($ORDER as $column => $value) {
                        if (is_array($value)) {
                            $stack[] = 'FIELD(' . $this->columnQuote($column) . ', ' . $this->arrayQuote($value) . ')';
                        } elseif ($value === 'ASC' || $value === 'DESC') {
                            $stack[] = $this->columnQuote($column) . ' ' . $value;
                        } elseif (is_int($column)) {
                            $stack[] = $this->columnQuote($value);
                        }
                    }
                    $where_clause .= ' ORDER BY ' . implode($stack, ',');
                } else {
                    $where_clause .= ' ORDER BY ' . $this->columnQuote($ORDER);
                }
                if (isset($where['LIMIT']) && in_array($this->database_type, [self::DB_TYPE_ORACLE, self::DB_TYPE_MSSQL])) {
                    $LIMIT = $where['LIMIT'];
                    if (is_numeric($LIMIT)) {
                        $where_clause .= ' FETCH FIRST ' . $LIMIT . ' ROWS ONLY';
                    }
                    if (is_array($LIMIT) && is_numeric($LIMIT[0]) && is_numeric($LIMIT[1])) {
                        $where_clause .= ' OFFSET ' . $LIMIT[0] . ' ROWS FETCH NEXT ' . $LIMIT[1] . ' ROWS ONLY';
                    }
                }
            }

            if (isset($where['LIMIT']) && !in_array($this->database_type, [self::DB_TYPE_ORACLE, self::DB_TYPE_MSSQL])) {
                $LIMIT = $where['LIMIT'];
                if (is_numeric($LIMIT)) {
                    $where_clause .= ' LIMIT ' . $LIMIT;
                }
                if (is_array($LIMIT) && is_numeric($LIMIT[0]) && is_numeric($LIMIT[1])) {
                    $where_clause .= ' LIMIT ' . $LIMIT[1] . ' OFFSET ' . $LIMIT[0];
                }
            }

        } else {
            if ($where !== NULL) {
                $where_clause .= ' ' . $where;
            }
        }

        return $where_clause;
    }

    protected function selectContext($table, &$map, $join, &$columns = NULL, $where = NULL, $column_fn = NULL) {
        preg_match('/(?<table>[a-zA-Z0-9_]+)\s*\((?<alias>[a-zA-Z0-9_]+)\)/i', $table, $table_match);

        if (isset($table_match['table'], $table_match['alias'])) {
            $table = $this->tableQuote($table_match['table']);
            $table_query = $table . ' AS ' . $this->tableQuote($table_match['alias']);
        } else {
            $table = $this->tableQuote($table);
            $table_query = $table;
        }
        $join_key = is_array($join) ? array_keys($join) : NULL;
        if (isset($join_key[0]) && strpos($join_key[0], '[') === 0) {
            $table_join = [];
            $join_array = ['>' => 'LEFT', '<' => 'RIGHT', '<>' => 'FULL', '><' => 'INNER'];
            foreach ($join as $sub_table => $relation) {
                preg_match('/(\[(?<join>\<|\>|\>\<|\<\>)\])?(?<table>[a-zA-Z0-9_]+)\s?(\((?<alias>[a-zA-Z0-9_]+)\))?/', $sub_table, $match);

                if ($match['join'] !== '' && $match['table'] !== '') {
                    if (is_string($relation)) {
                        $relation = 'USING ("' . $relation . '")';
                    }
                    if (is_array($relation)) {
                        // For ['column1', 'column2']
                        if (isset($relation[0])) {
                            $relation = 'USING ("' . implode($relation, '", "') . '")';
                        } else {
                            $joins = [];
                            foreach ($relation as $key => $value) {
                                $joins[] = (strpos($key, '.') > 0 ? // For ['tableB.column' => 'column']
                                        $this->columnQuote($key) : // For ['column1' => 'column2']
                                        $table . '."' . $key . '"') . ' = ' . $this->tableQuote(isset($match['alias']) ? $match['alias'] : $match['table']) . '."' . $value . '"';
                            }
                            $relation = 'ON ' . implode($joins, ' AND ');
                        }
                    }
                    $table_name = $this->tableQuote($match['table']) . ' ';
                    if (isset($match['alias'])) {
                        $table_name .= 'AS ' . $this->tableQuote($match['alias']) . ' ';
                    }
                    $table_join[] = $join_array[$match['join']] . ' JOIN ' . $table_name . $relation;
                }
            }
            $table_query .= ' ' . implode($table_join, ' ');
        } else {
            if (is_null($columns)) {
                if (is_null($where)) {
                    if (is_array($join) && isset($column_fn)) {
                        $where = $join;
                        $columns = NULL;
                    } else {
                        $where = NULL;
                        $columns = $join;
                    }
                } else {
                    $where = $join;
                    $columns = NULL;
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
            } else {
                if (empty($columns)) {
                    $columns = '*';
                    $where = $join;
                }
                $column = $column_fn . '(' . $this->columnPush($columns) . ')';
            }
        } else {
            $column = $this->columnPush($columns);
        }

        return 'SELECT ' . $column . ' FROM ' . $table_query . $this->whereClause($where, $map);
    }

    protected function columnMap($columns, &$stack) {
        if ($columns === '*') {
            return $stack;
        }

        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                preg_match('/(?<column>[a-zA-Z0-9_\.]*)(?:\s*\((?<alias>[a-zA-Z0-9_]+)\)|\s*\[(?<type>(String|Bool|Int|Number|Object|JSON))\])?/i', $value, $key_match);
                $column_key = !empty($key_match['alias']) ? $key_match['alias'] : preg_replace('/^[\w]*\./i', '', $key_match['column']);
                if (isset($key_match['type'])) {
                    $stack[$value] = [$column_key, $key_match['type']];
                } else {
                    $stack[$value] = [$column_key, 'String'];
                }
            } else {
                $this->columnMap($value, $stack);
            }
        }

        return $stack;
    }

    protected function dataMap($data, $columns, $column_map, &$stack) {
        foreach ($columns as $key => $value) {
            if (is_int($key)) {
                $map = $column_map[$value];
                $column_key = $map[0];
                if (isset($map[1])) {
                    switch ($map[1]) {
                        case 'Number':
                        case 'Int':
                            $stack[$column_key] = (int) $data[$column_key];
                            break;
                        case 'Bool':
                            $stack[$column_key] = (bool) $data[$column_key];
                            break;
                        case 'Object':
                            $stack[$column_key] = unserialize($data[$column_key]);
                            break;
                        case 'JSON':
                            $stack[$column_key] = json_decode($data[$column_key], TRUE);
                            break;
                        case 'String':
                            $stack[$column_key] = $data[$column_key];
                            break;
                    }
                } else {
                    $stack[$column_key] = $data[$column_key];
                }
            } else {
                $current_stack = [];
                $this->dataMap($data, $value, $column_map, $current_stack);
                $stack[$key] = $current_stack;
            }
        }
    }

    public function select($table, $join, $columns = NULL, $where = NULL) {
        $this->setWRMode(FALSE);

        $map = [];
        $stack = [];
        $column_map = [];
        $index = 0;
        $column = $where === NULL ? $join : $columns;
        $is_single_column = (is_string($column) && $column !== '*');
        $query = $this->exec($this->selectContext($table, $map, $join, $columns, $where), $map);
        $this->columnMap($columns, $column_map);
        if (!$query) {
            return FALSE;
        }

        if ($columns === '*') {
            return $query->fetchAll(PDO::FETCH_ASSOC);
        }
        if ($is_single_column) {
            return $query->fetchAll(PDO::FETCH_COLUMN);
        }
        while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
            $current_stack = [];
            $this->dataMap($data, $columns, $column_map, $current_stack);
            $stack[$index] = $current_stack;
            $index++;
        }

        return $stack;
    }

    public function insert($table, $datas) {
        $this->setWRMode(TRUE);

        $stack = [];
        $columns = [];
        $fields = [];
        $map = [];
        if (!isset($datas[0])) {
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
                if (strpos($key, '#') === 0) {
                    $values[] = $this->fnQuote($key, $data[$key]);
                    continue;
                }
                $map_key = $this->mapKey();
                $values[] = $map_key;
                if (!isset($data[$key])) {
                    $map[$map_key] = [NULL, PDO::PARAM_NULL];
                } else {
                    $value = $data[$key];
                    switch (gettype($value)) {
                        case 'NULL':
                            $map[$map_key] = [NULL, PDO::PARAM_NULL];
                            break;
                        case 'array':
                            $map[$map_key] = [strpos($key, '[JSON]') === strlen($key) - 6 ? json_encode($value) : serialize($value), PDO::PARAM_STR];
                            break;
                        case 'object':
                            $map[$map_key] = [serialize($value), PDO::PARAM_STR];
                            break;
                        case 'resource':
                            $map[$map_key] = [$value, PDO::PARAM_LOB];
                            break;
                        case 'boolean':
                            $map[$map_key] = [($value ? '1' : '0'), PDO::PARAM_BOOL];
                            break;
                        case 'integer':
                        case 'double':
                            $map[$map_key] = [$value, PDO::PARAM_INT];
                            break;
                        case 'string':
                            $map[$map_key] = [$value, PDO::PARAM_STR];
                            break;
                    }
                }
            }
            $stack[] = '(' . implode($values, ', ') . ')';
        }
        foreach ($columns as $key) {
            $fields[] = $this->columnQuote(preg_replace("/(^#|\s*\[JSON\]$)/i", '', $key));
        }

        return $this->exec('INSERT INTO ' . $this->tableQuote($table) . ' (' . implode(', ', $fields) . ') VALUES ' . implode(', ', $stack), $map);
    }

    public function update($table, $data, $where = NULL) {
        $this->setWRMode(TRUE);

        $fields = [];
        $map = [];
        foreach ($data as $key => $value) {
            $column = $this->columnQuote(preg_replace("/(^#|\s*\[(JSON|\+|\-|\*|\/)\]$)/i", '', $key));
            if (strpos($key, '#') === 0) {
                $fields[] = $column . ' = ' . $value;
                continue;
            }
            $map_key = $this->mapKey();
            preg_match('/(?<column>[a-zA-Z0-9_]+)(\[(?<operator>\+|\-|\*|\/)\])?/i', $key, $match);
            if (isset($match['operator'])) {
                if (is_numeric($value)) {
                    $fields[] = $column . ' = ' . $column . ' ' . $match['operator'] . ' ' . $value;
                }
            } else {
                $fields[] = $column . ' = ' . $map_key;
                switch (gettype($value)) {
                    case 'NULL':
                        $map[$map_key] = [NULL, PDO::PARAM_NULL];
                        break;
                    case 'array':
                        $map[$map_key] = [strpos($key, '[JSON]') === strlen($key) - 6 ? json_encode($value) : serialize($value), PDO::PARAM_STR];
                        break;
                    case 'object':
                        $map[$map_key] = [serialize($value), PDO::PARAM_STR];
                        break;
                    case 'resource':
                        $map[$map_key] = [$value, PDO::PARAM_LOB];
                        break;
                    case 'boolean':
                        $map[$map_key] = [($value ? '1' : '0'), PDO::PARAM_BOOL];
                        break;
                    case 'integer':
                    case 'double':
                        $map[$map_key] = [$value, PDO::PARAM_INT];
                        break;
                    case 'string':
                        $map[$map_key] = [$value, PDO::PARAM_STR];
                        break;
                }
            }
        }

        return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . implode(', ', $fields) . $this->whereClause($where, $map), $map);
    }

    public function delete($table, $where) {
        $this->setWRMode(TRUE);

        $map = [];

        return $this->exec('DELETE FROM ' . $this->tableQuote($table) . $this->whereClause($where, $map), $map);
    }

    public function replace($table, $columns, $where = NULL) {
        $this->setWRMode(TRUE);

        $map = [];
        if (is_array($columns)) {
            $replace_query = [];
            foreach ($columns as $column => $replacements) {
                if (is_array($replacements[0])) {
                    foreach ($replacements as $replacement) {
                        $map_key = $this->mapKey();
                        $replace_query[] = $this->columnQuote($column) . ' = REPLACE(' . $this->columnQuote($column) . ', ' . $map_key . 'a, ' . $map_key . 'b)';
                        $map[$map_key . 'a'] = [$replacement[0], PDO::PARAM_STR];
                        $map[$map_key . 'b'] = [$replacement[1], PDO::PARAM_STR];
                    }
                } else {
                    $map_key = $this->mapKey();
                    $replace_query[] = $this->columnQuote($column) . ' = REPLACE(' . $this->columnQuote($column) . ', ' . $map_key . 'a, ' . $map_key . 'b)';
                    $map[$map_key . 'a'] = [$replacements[0], PDO::PARAM_STR];
                    $map[$map_key . 'b'] = [$replacements[1], PDO::PARAM_STR];
                }
            }
            $replace_query = implode(', ', $replace_query);
        }

        return $this->exec('UPDATE ' . $this->tableQuote($table) . ' SET ' . $replace_query . $this->whereClause($where, $map), $map);
    }

    public function get($table, $join = NULL, $columns = NULL, $where = NULL) {
        $this->setWRMode(FALSE);

        $map = [];
        $stack = [];
        $column_map = [];
        $column = $where === NULL ? $join : $columns;
        $is_single_column = (is_string($column) && $column !== '*');

        $query = $this->exec($this->selectContext($table, $map, $join, $columns, $where) . ' LIMIT 1', $map);

        if ($query) {
            $data = $query->fetchAll(PDO::FETCH_ASSOC);
            if (isset($data[0])) {
                if ($column === '*') {
                    return $data[0];
                }
                $this->columnMap($columns, $column_map);
                $this->dataMap($data[0], $columns, $column_map, $stack);
                if ($is_single_column) {
                    return $stack[$column_map[$column][0]];
                }

                return $stack;
            } else {
                return FALSE;
            }
        }

        return FALSE;
    }

    public function has($table, $join, $where = NULL) {
        $this->setWRMode(FALSE);

        $map = [];
        $column = NULL;
        $query = $this->exec('SELECT EXISTS(' . $this->selectContext($table, $map, $join, $column, $where, 1) . ')', $map);
        if ($query) {
            return $query->fetchColumn() === '1';
        } else {
            return FALSE;
        }
    }

    public function count($table, $join = NULL, $column = NULL, $where = NULL) {
        $this->setWRMode(FALSE);

        $map = [];
        $query = $this->exec($this->selectContext($table, $map, $join, $column, $where, 'COUNT'), $map);

        return $query ? 0 + $query->fetchColumn() : FALSE;
    }

    public function max($table, $join, $column = NULL, $where = NULL) {
        $this->setWRMode(FALSE);

        $map = [];
        $query = $this->exec($this->selectContext($table, $map, $join, $column, $where, 'MAX'), $map);
        if ($query) {
            $max = $query->fetchColumn();

            return is_numeric($max) ? $max + 0 : $max;
        } else {
            return FALSE;
        }
    }

    public function min($table, $join, $column = NULL, $where = NULL) {
        $this->setWRMode(FALSE);

        $map = [];
        $query = $this->exec($this->selectContext($table, $map, $join, $column, $where, 'MIN'), $map);
        if ($query) {
            $min = $query->fetchColumn();

            return is_numeric($min) ? $min + 0 : $min;
        } else {
            return FALSE;
        }
    }

    public function avg($table, $join, $column = NULL, $where = NULL) {
        $this->setWRMode(FALSE);

        $map = [];
        $query = $this->exec($this->selectContext($table, $map, $join, $column, $where, 'AVG'), $map);

        return $query ? 0 + $query->fetchColumn() : FALSE;
    }

    public function sum($table, $join, $column = NULL, $where = NULL) {
        $this->setWRMode(FALSE);

        $map = [];
        $query = $this->exec($this->selectContext($table, $map, $join, $column, $where, 'SUM'), $map);

        return $query ? 0 + $query->fetchColumn() : FALSE;
    }

    public function action(callable $actions) {
        $this->setWRMode(TRUE);

        $this->getConnection()->beginTransaction();
        $result = $actions($this);
        if ($result === FALSE) {
            $this->getConnection()->rollBack();
        } else {
            $this->getConnection()->commit();
        }
    }

    public function id() {
        $type = $this->database_type;

        if ($type === self::DB_TYPE_ORACLE) {
            return 0;
        } elseif ($type === self::DB_TYPE_MSSQL) {
            return $this->getConnection()->query('SELECT SCOPE_IDENTITY()')->fetchColumn();
        } elseif ($type === self::DB_TYPE_PGSQL) {
            return $this->getConnection()->query('SELECT LASTVAL()')->fetchColumn();
        }

        return $this->getConnection()->lastInsertId();
    }

    public function debug() {
        $this->debug_mode = TRUE;

        return $this;
    }

    public function error() {
        return $this->statement ? $this->statement->errorInfo() : NULL;
    }

    public function info() {
        $output = ['server' => 'SERVER_INFO', 'driver' => 'DRIVER_NAME', 'client' => 'CLIENT_VERSION', 'version' => 'SERVER_VERSION', 'connection' => 'CONNECTION_STATUS'];
        foreach ($output as $key => $value) {
            $output[$key] = @$this->getConnection()->getAttribute(constant('PDO::ATTR_' . $value));
        }

        return $output;
    }

    public function setWRMode($isWriteMode) {
        $this->isWR = $isWriteMode;
    }
}
