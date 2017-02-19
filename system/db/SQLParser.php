<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:05.
 */

namespace Akari\system\db;

class SQLParser
{
    /**
     * @var \PDO
     */
    protected $pdo;
    protected static $p;
    protected $autoBind = true;

    public $_bind = [];
    public $_bindCount = 0;

    public static function getInstance(\PDO $pdo)
    {
        if (!isset(self::$p)) {
            self::$p = new self();
            self::$p->pdo = $pdo;
        }

        return self::$p;
    }

    protected function __construct()
    {
    }

    public function clearBind()
    {
        $this->_bind = [];
        $this->_bindCount = 0;
    }

    /**
     * 是否使用parser的自动绑定。
     * 如果开启，需要再完成prepare解析后单独对parser的_bind进行循环绑定。
     *
     * 默认DBAgentStatement已经实现，建议开启（默认开启）
     *
     * @param bool $setTo 设定为
     *
     * @return void
     */
    protected function _setAutoBind($setTo = true)
    {
        $this->autoBind = (bool) $setTo;
    }

    /**
     * 过滤value.
     *
     * @param string $value 值
     *
     * @return string
     **/
    public function parseValue($value)
    {
        if ($this->autoBind) {
            $SQLHeader = '_PDR';

            // 调试时为了不覆盖 变量2位
            $bindCount = str_pad($this->_bindCount, 2, '0', STR_PAD_LEFT);
            $this->_bindCount++;

            $this->_bind[$SQLHeader.$bindCount] = $value;

            return ':'.$SQLHeader.$bindCount;
        }

        return $this->pdo->quote($value);
    }

    /**
     * 处理列名.
     *
     * @param string $value 名
     *
     * @return string
     **/
    public function parseColumn($value)
    {
        if (stripos($value, '.') !== false) {
            list($tblName, $itemName) = explode('.', $value);

            return '`'.$tblName.'`.`'.$itemName.'`';
        }

        return '`'.$value.'`';
    }

    /**
     * 分析处理查询的列.
     *
     * @param mixed $columns 条件
     *
     * @return string
     **/
    public function parseField($columns)
    {
        if ($columns == '*') {
            return '*';
        }

        if (is_string($columns)) {
            $columns = [$columns];
        }

        $stack = [];
        $fieldComplex = ['count(', 'sum(', 'count (', 'sum ('];

        foreach ($columns as $key => $value) {
            preg_match('/([a-zA-Z0-9_\-\.]*)\s*\(([a-zA-Z0-9_\-]*)\)/i', $value, $match);

            // 如果有count 就不进行parseColumn
            foreach ($fieldComplex as $complex) {
                if (stripos($value, $complex) !== false) {
                    array_push($stack, $value);
                    continue 2;
                }
            }

            if (isset($match[1], $match[2])) {
                array_push($stack, $this->parseColumn($match[1]).' AS '.$this->parseColumn($match[2]));
            } else {
                array_push($stack, $this->parseColumn($value));
            }
        }

        return implode(',', $stack);
    }

    /**
     * 过滤数组内容.
     *
     * @param array $array 数组内容
     *
     * @return string
     **/
    public function parseArray($array)
    {
        $temp = [];
        foreach ($array as $value) {
            $temp[] = is_numeric($value) ? $value : $this->parseValue($value);
        }

        return implode(',', $temp);
    }

    /**
     * 内联.
     *
     * @param array  $data  数据
     * @param string $flag  标志
     * @param string $outer 外接字符
     *
     * @return string
     **/
    public function innerIn($data, $flag, $outer)
    {
        $haystack = [];
        foreach ($data as $value) {
            $haystack[] = '('.$this->parseData($value, $flag).')';
        }

        return implode($outer.' ', $haystack);
    }

    public function parseData($data, $flag = ' AND', $onWhere = false)
    {
        $wheres = [];

        if (gettype($data) == 'object') {
            $data = (array) $data;
        }

        foreach ($data as $key => $value) {
            if ($onWhere) {
                $key = $value[0];
                $onField = $value[2];
                $value = $value[1];

                if ($onField) {
                    $wheres[] = "$key = $value";
                    continue;
                }
            }

            $type = gettype($value);

            if ($type == 'array' && preg_match("/^(AND|OR)\s*#?/i", $key, $relation_match)) {
                $wheres[] = 0 !== count(array_diff_key($value, array_keys(array_keys($value)))) ?
                    '('.$this->parseData($value, ' '.$relation_match[1]).')' :
                    '('.$this->innerIn($value, ' '.$relation_match[1], $flag).')';
            } else {
                // 匹配运算符用
                preg_match('/([\w\.]+)(\[(\>|\>\=|\<|\<\=|\!|\<\>|\?|\>\<)\])?/i', $key, $match);
                $column = $this->parseColumn($match[1]);

                if (isset($match[3])) {
                    if ($match[3] == '') {
                        $wheres[] = $column.' '.$match[3].'= '.$this->parseValue($value);
                    } elseif ($match[3] == '!') {
                        switch ($type) {
                            case 'NULL':
                                $wheres[] = "$column IS NOT NULL";
                                break;
                            case 'array':
                                $wheres[] = "$column NOT IN (".$this->parseArray($value).')';
                                break;

                            case 'integer':
                            case 'double':
                                $wheres[] = "$column != ".$this->parseValue($value);
                                break;

                            case 'boolean':
                                $wheres[] = "$column != ".($value ? '1' : '0');
                                break;

                            case 'string':
                                $wheres[] = "$column != ".$this->parseValue($value);
                                break;
                        }
                    } elseif ($match[3] == '<>' || $match[3] == '><') {
                        if ($type == 'array') {
                            if ($match[3]) {
                                $column .= ' NOT';
                            }

                            if (is_numeric($value[0]) && is_numeric($value[1])) {
                                $wheres[] = '('.$column.' BETWEEN '.$value[0].' AND '.$value[1].')';
                            } else {
                                $wheres[] = '('.$column.' BETWEEN '.$this->parseValue($value[0]).' AND '.$this->parseValue($value[1]).')';
                            }
                        }
                    } elseif ($match[3] == '?') {
                        $wheres[] = $match[1].' LIKE '.$this->parseValue($value);
                    } else {
                        //都不是那就是日期
                        if (is_numeric($value)) {
                            $wheres[] = "$column $match[3] $value";
                        } else {
                            $datetime = strtotime($value);
                            if ($datetime) {
                                $wheres[] = "$column $match[3] ".$this->parseValue(date('Y-m-d H:i:s', $datetime));
                            }
                        }
                    }
                } else {
                    // 没有特殊运算时 那就是＝了
                    switch ($type) {
                        case 'NULL':
                            $wheres[] = "$column IS NULL";
                            break;

                        case 'array':
                            $wheres[] = "$column IN (".$this->parseArray($value).')';
                            break;

                        case 'integer':
                        case 'double':
                            $wheres[] = "$column = ".$this->parseValue($value);
                            break;

                        case 'boolean':
                            $wheres[] = "$column = ".($value ? '1' : '0');
                            break;

                        case 'string':
                            $wheres[] = "$column = ".$this->parseValue($value);
                            break;
                    }
                }
            }
        }

        return implode($flag.' ', $wheres);
    }

    public function parseWhere($where)
    {
        $str = '';
        if (is_array($where)) {
            $whereKeys = array_keys($where);
            $whereAND = preg_grep("/^AND\s*#?$/i", $whereKeys);
            $whereOR = preg_grep("/^OR\s*#?$/i", $whereKeys);

            $singleOper = array_diff_key($where, array_flip(
                explode(' ', 'AND OR GROUP ORDER HAVING LIMIT LIKE MATCH')
            ));

            //如果找到操作符 那么后面的可以无视了
            if ($singleOper != []) {
                $str = $this->parseData($singleOper[0], '');
            }

            if (!empty($whereAND)) {
                $value = array_values($whereAND);
                $str = $this->parseData($where[$value[0]], ' AND');
            }

            if (!empty($whereOR)) {
                $value = array_values($whereOR);
                $str = $this->parseData($where[$value[0]], ' OR');
            }

            if (isset($where['LIKE'])) {
                $likeTmpStr = ''; //@todo
                $likes = $where['LIKE'];

                if (is_array($likes)) {
                    $isOR = isset($likes['OR']);
                    $wrap = [];

                    if ($isOR || isset($likes['AND'])) {
                        $connector = $isOR ? 'OR' : 'AND';
                        $likes = $isOR ? $likes['OR'] : $likes['AND'];
                    } else {
                        $connector = 'AND';
                    }

                    foreach ($likes as $column => $keyword) {
                        if (is_array($keyword)) {
                            foreach ($keyword as $key) {
                                $wrap[] = $this->parseColumn($column).' LIKE '.$this->parseValue('%'.$value.'%');
                            }
                        } else {
                            $wrap[] = $this->parseColumn($column).' LIKE '.$this->parseValue('%'.$keyword.'%');
                        }
                    }

                    $likeTmpStr .= '('.implode($wrap, ' '.$connector.' ').')';
                    $str = $str == '' ? $likeTmpStr : " AND $likeTmpStr";

                    unset($likeTmpStr);
                }
            }

            if (isset($where['MATCH'])) {
                $matchTmpStr = '';
                $matchQuery = $where['MATCH'];

                if (is_array($matchQuery) && isset($matchQuery['columns'], $matchQuery['keyword'])) {
                    $str = ($str == '' ? '' : ' AND').' MATCH ("'.str_replace('.', '"."', implode($matchQuery['columns'], '", "')).'") AGAINST ('.$this->quote($matchQuery['keyword']).')';
                }
            }
        } else {
            if ($where != null) {
                $str = ' '.$where;
            }
        }

        return $str != '' ? " WHERE $str " : '';
    }

    public function parseJoin($join)
    {
        $joinKey = is_array($join) ? array_keys($join) : null;

        if (isset($joinKey[0]) && $joinKey[0][0] == '[') {
            $tableJoin = [];
            $joinArray = [
                '>'  => 'LEFT', '<' => 'RIGHT',
                '<>' => 'FULL', '><' => 'INNER',
            ];

            foreach ($join as $subTable => $relation) {
                preg_match('/(\[(\<|\>|\>\<|\<\>)\])?([a-zA-Z0-9_\-]*)/', $subTable, $match);

                if ($match[2] != '' && $match[3] != '') {
                    if (is_string($relation)) {
                        $relation = "USING (\"$relation\")";
                    } elseif (is_array($relation)) {
                        if (isset($relation[0])) {
                            // ['table1', 'table2']
                            $relation = 'USING ('.implode('", "', $relation).'")';
                        } else {
                            // ['table1' => 'table2']
                            $relation = 'ON '.$subTable.'."'.key($relation).'" = "'.$match[3].'"."'.current($relation).'"';
                        }
                    }

                    $tableJoin[] = $joinArray[$match[2]].' JOIN "'.$match[3].'" '.$relation;
                }
            }

            return implode(' ', $tableJoin);
        }

        return '';
    }

    public function parseLimit($limit)
    {
        if (is_numeric($limit)) {
            return " LIMIT $limit";
        } elseif (is_array($limit) && is_numeric($limit[0]) && is_numeric($limit[1])) {
            return " LIMIT $limit[0],$limit[1]";
        }

        return '';
    }

    public function parseOrder($order)
    {
        if (empty($order)) {
            return '';
        }

        if (is_array($order)) {
            return ' ORDER BY FIELD('.$this->parseColumn($order[0]).', '.$this->parseArray($order[1]).')';
        }

        preg_match('/(^[a-zA-Z0-9_\-\.]*)(\s*(DESC|ASC))?/', $order, $match);

        return ' ORDER BY `'.str_replace('.', '"."', $match[1]).'` '.(isset($match[3]) ? $match[3] : '');
    }

    public function parseHaving($having)
    {
        return empty($having) ? '' : ' HAVING '.$this->parseData($having, '');
    }

    public function parseGroup($group)
    {
        return empty($group) ? '' : ' GROUP BY '.$this->parseColumn($group);
    }

    public function parseSet($data)
    {
        $values = [];

        foreach ($data as $key => $value) {
            switch (gettype($value)) {
                case 'NULL':
                    $value[$key] = 'NULL';
                    break;

                case 'array':
                    preg_match("/\(JSON\)\s*([\w]+)/i", $key, $columnMatch);
                    if (isset($columnMatch[0])) {
                        $values[$columnMatch[1]] = $this->parseValue(json_encode($value));
                    } else {
                        $values[$key] = $this->parseValue(serialize($value));
                    }
                    break;

                case 'boolean':
                    $values[$key] = ($value ? '1' : '0');
                    break;

                case 'integer':
                case 'double':
                case 'string':
                    $values[$key] = $this->parseValue($value);
                    break;
            }
        }

        return $values;
    }

    public function parseDistinct($field)
    {
        return ' DISTINCT('.$field.') ';
    }
}
