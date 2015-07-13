<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:05
 */

namespace Akari\system\db;

Class DBAgentStatement {

    protected $agent;
    protected $SQL;

    /**
     * @var $stmt \PDOStatement
     */
    protected $stmt;

    /**
     * @var $parser SQLParser
     */
    protected $parser;
    protected $_parsedSQL;

    private $_bind = [];
    private $_args = [];

    public function __construct($SQL, DBAgent $dbAgent) {
        $this->pdo = $dbAgent->getPDOInstance();
        $this->SQL = $SQL;
        $this->parser = SQLParser::getInstance($this->pdo);
    }

    public function close() {
        $this->stmt->closeCursor();
        $this->_parsedSQL = NULL;
        $this->_bind = [];
        $this->_args = [];
    }

    /**
     * 在当前SQL语句后面追加str
     *
     * @param string $str
     */
    public function addSQL($str) {
        $this->SQL .= $str;
    }

    /**
     * PDO的参数绑定
     * 举例: bindValue('a', 'b')会绑定sql语句中的:a
     *
     * @param string $key 键
     * @param string|int $value 值
     */
    public function bindValue($key, $value) {
        $this->_bind[':'. $key] = $value;
    }

    /**
     * 添加排序值，如id DESC
     * 可多次调用
     *
     * @param $order
     */
    public function addOrder($order) {
        $this->_args['ORDER'][] = $order;
    }

    /**
     * 添加where的替换字段，SQL语句需要有(where)用来替换
     * 举例添加一个field=a,value=b 那么会自动替换成 WHERE a = 'b'
     * 此外语句中如果已经有WHERE时，(where)的替换开头会自动变成 AND `a` = 'b'
     * 此外value如果为数组时，自动会解析为IN查询
     *
     * @param string $field
     * @param mixed $value
     */
    public function addWhere($field, $value) {
        $this->_args['WHERE'][$field] = $value;
    }

    /**
     * 注意和addWhere不同，调用的是parse的parseWhere的方法
     * 即支持AND/OR的方法【必须写嗯】
     *
     * @param array $data
     */
    public function setWhere($data) {
        $this->_args['WHERES'] = $data;
    }

    /**
     * 设置SQL的LIMIT
     * 传入[0, 10] => LIMIT 0, 10  传入10 => LIMIT 10
     *
     * @param int|array $limit
     */
    public function setLimit($limit) {
        $this->_args['LIMIT'] = $this->parser->parseLimit($limit);
    }

    /**
     * 类似WHERE，会替换SQL语句中的(data)
     * 会替换成`field1` = 'value1', `field2` = 'value2'
     *
     * @param string $field
     * @param string|int $value
     */
    public function addData($field, $value) {
        $this->_args['DATA'][$field] = $value;
    }

    /**
     * 参考addData，只是一次性设定DATA的值
     * 传入一个数组
     *
     * @param array $data
     */
    public function setData($data) {
        $this->_args['DATA'] = $data;
    }

    /**
     * 获得被解析后的SQL（不包括bind）
     *
     * @return string
     */
    public function getParsedSQL() {
        if (!empty($this->_parsedSQL)) {
            return $this->_parsedSQL;
        }

        $parser = $this->parser;
        $sql = $this->SQL;

        if (!empty($this->_args['WHERES'])) {
            $sql = str_replace('(where)', $parser->parseWhere($this->_args['WHERES']), $sql);
        }

        if (!empty($this->_args['WHERE'])) {
            $where = $parser->parseData($this->_args['WHERE']);

            if(!empty($where)){
                // 注意找的是where空格 不是(where)
                $replace = (stripos($sql, " where ")===FALSE ? " WHERE" : " AND")." ".$where;
                $sql = str_replace('(where)', $replace, $sql);
            }
        }
        $sql = str_ireplace('(where)', '', $sql);

        if (!empty($this->_args['DATA'])) {
            $data = [];
            foreach ($this->_args['DATA'] as $key => $value) {
                $data[] = "`$key` = ".$parser->parseValue($value);
            }

            $sql = str_ireplace("(data)", implode(",", $data), $sql);
        }
        $sql = str_ireplace("(data)", "", $sql);

        if (!empty($this->_args['ORDER'])) {
            $sql .= " ORDER BY ".implode(",", $this->_args['ORDER']);
        }

        if (!empty($this->_args['LIMIT'])) {
            $sql .= $this->_args['LIMIT'];
        }

        foreach ($parser->_bind as $key => $value) {
            $this->bindValue($key, $value);
        }
        $parser->clearBind();

        $this->_parsedSQL = $sql;

        return $sql;
    }

    /**
     * 获得调试用SQL语句
     *
     * @return string
     */
    public function getDebugSQL() {
        $sql = $this->getParsedSQL();
        if (!empty($this->_bind)) {
            krsort($this->_bind);
            foreach ($this->_bind as $key => $value) {
                $sql = str_replace($key, "'".$value."'", $sql);
            }
        }

        return $sql;
    }

    /**
     * 获得PDOStatement对象
     *
     * @return \PDOStatement
     * @throws DBAgentException
     */
    public function getDoc() {
        $sql = $this->getParsedSQL();

        try {
            $this->stmt = $this->pdo->prepare($sql);
        } catch (\PDOException $e) {
            throw new DBAgentException('SQL prepared error [' . $e->getCode() . '], Message: ' . $e->getMessage() . '. SQL: ' . $sql . PHP_EOL . ' With PDO Message:' . $this->pdo->errorInfo()[2]);
        }

        if (!empty($this->_bind)) {
            foreach ($this->_bind as $key => $value) {
                $this->stmt->bindValue($key, $value);
            }
        }

        return $this->stmt;
    }

}