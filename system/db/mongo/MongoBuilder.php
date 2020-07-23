<?php

namespace Akari\system\db\mongo;

use MongoDB\BSON\Regex;
use MongoDB\BSON\ObjectId;

class MongoBuilder
{

    protected $conn;
    protected $collectionName;
    protected $usingObjectId = TRUE;
    protected $usingNULL = TRUE;

    public function __construct(MongoConnection $connection, string $collectionName, array $options) {
        $this->conn = $connection;
        $this->collectionName = $collectionName;

        $this->usingObjectId = $options['usingObjectId'] ?? TRUE;
        $this->usingNULL = $options['usingNULL'] ?? TRUE;
    }

    protected function getQuery() {
        return $this->conn->getQuery()->selectCollection($this->collectionName);
    }

    protected function parseConds(array $data) {
        $options = [];

        if (array_key_exists('conditions', $data)) {
            $conditions = $data['conditions'];
        } else {
            $conditions = array_diff_key($data, array_flip(
                ['ORDER', 'LIMIT', 'SKIP', 'SORT', 'FILEDS']
            ));
        }

        $opOrder = $data['ORDER'] ?? $data['order'] ?? $data['SORT'] ?? $data['sort'] ?? NULL;
        if ($opOrder !== NULL) {
            $options['sort'] = $opOrder;
        }

        $opFields = $data['FILEDS'] ?? $data['fields'] ?? NULL;
        if ($opFields !== NULL) {
            // $options['returnKey'] = $opFields;
        }

        $opLimit = $data['LIMIT'] ?? $data['limit'] ?? NULL;
        if ($opLimit !== NULL) {
            $options['limit'] = (int) $opLimit;
        }

        $opSkip = $data['SKIP'] ?? $data['skip'] ?? NULL;
        if ($opSkip !== NULL) {
            $options['skip'] = (int) $opSkip;
        }

        return [$conditions, $options];
    }

    protected function parseFilterConds(array $data) {
        // 这里我们简单的将[>] [<] 这些情况进行拆分
        $stack = [];

        foreach ($data as $key => $value) {
            $type = gettype($value);

            // 这里先判断是否有操作符 如果有操作符的话就进行解析 如果没有的话就跳过
            if (
                $type === 'array' &&
                preg_match("/^(AND|OR)(\s+#.*)?$/", $key, $relation_match)
            ) {
                $relationship = $relation_match[ 1 ];
                $pValues = [];
                foreach ($value as $val) {
                    $pValues[] = $this->parseFilterConds($val);
                }

                $stack['$' . $relationship] = $pValues;

                continue;
            }

            preg_match('/([a-zA-Z0-9_\.]+)(\[(?<operator>\>\=?|\<\=?|\!|\<\>|\>\<|\!?~|REGEXP)\])?/i', $key, $match);
            $column = $match[1];

            if ($column === '_id' && $this->usingObjectId && $type === 'string') {
                $value = new ObjectId($value);
            }

            if (isset($match[ 'operator' ])) {
                $operator = $match[ 'operator' ];
                $operMap = [
                    '>' => '$gt',
                    '>=' => '$gte',
                    '<' => '$lt',
                    '<=' => '$lte',
                ];

                // 如果有操作符的话
                if (isset($operMap[$operator])) {
                    if (!isset($stack[$column])) $stack[$column] = [];
                    $stack[$column][$operMap[$operator]] = (int) $value;
                } elseif ($operator === '!') {
                    if (!isset($stack[$column])) $stack[$column] = [];

                    switch ($type) {
                        case 'array':
                            $stack[$column]['$nin'] = $value;
                            break;
                        default:
                            $stack[$column]['$ne'] = $value;
                            break;
                    }

                    continue;
                } elseif ($operator === '~') {
                    $stack[$column] = $value instanceof Regex ? $value : new Regex($value);
                }
            } else {
                if (isset($stack[$column]) && !is_array($value)) {
                    $stack[$column]['$eq'] = $value;
                } else {
                    $stack[$column] = $value;
                }
            }

        }

        return $stack;
    }

    protected function parseUpdateFields(array $data) {
        $stack = [];
        foreach ($data as $key => $value) {
            $column = preg_replace("/(\s*\[(JSON|\+|\-|\*|\/)\]$)/i", '', $key);

            preg_match('/(?<column>[a-zA-Z0-9_]+)(\[(?<operator>\+|\-|\*|\/)\])?/i', $key, $match);

            if (isset($match['operator'])) {
                $operator = $match['operator'];
                if ($operator === '+') {
                    $stack['$inc'][$column] = (int) $value;
                } elseif ($operator === '-') {
                    $stack['$inc'][$column] = (int) -$value;
                }
            } else {
                if (!$this->usingNULL && $value === NULL) {
                    $value = '';
                }
                $stack['$set'][$column] = $value;
            }
        }

        return $stack;
    }

    public function find(array $conditions) {
        list($filter, $options) = $this->parseConds($conditions);
        $filter = $this->parseFilterConds($filter);

        return $this->getQuery()->find($filter, $options);
    }

    public function findFirst(array $conditions) {
        list($filter, $options) = $this->parseConds($conditions);
        $filter = $this->parseFilterConds($filter);

        return $this->getQuery()->findOne($filter, $options);
    }

    public function update(array $filter, array $data) {
        $filter = $this->parseFilterConds($filter);
        $data = $this->parseUpdateFields($data);

        return $this->getQuery()->updateMany($filter, $data);
    }

    public function insert(array $data) {
        foreach ($data as $key => $value) {
            if (!$this->usingNULL && $value === NULL) {
                $data[$key] = '';
            }
        }

        return $this->getQuery()->insertOne($data);
    }

    public function delete(array $filter, array $options = []) {
        return $this->getQuery()->deleteMany($filter, $options);
    }

    public function count(array $filter) {
        $filter = $this->parseFilterConds($filter);

        return $this->getQuery()->countDocuments($filter);
    }

}
