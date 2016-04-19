<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:05
 */

namespace Akari\system\db;

use Akari\utility\PageHelper;

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
    
    protected $pdo;

    private $_bind = [];
    private $_args = [];

    public function __construct($SQL, \PDO $pdo) {
        $this->SQL = $SQL;
        $this->parser = SQLParser::getInstance($pdo);
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
     * @return $this
     */
    public function addSQL($str) {
        $this->SQL .= $str;
        return $this;
    }

    /**
     * PDO的参数绑定
     * 举例: bindValue('a', 'b')会绑定sql语句中的:a
     *
     * @param string $key 键
     * @param string|int $value 值
     * @return $this
     */
    public function bindValue($key, $value) {
        $this->_bind[':'. $key] = $value;
        return $this;
    }

    /**
     * 添加排序值，如id DESC
     * 可多次调用
     *
     * @param $order
     * @return $this
     */
    public function addOrder($order) {
        $this->_args['ORDER'][] = $order;
        return $this;
    }

    /**
     * 添加where的替换字段，SQL语句需要有(where)用来替换<br />
     * 举例添加一个field=a,value=b 那么会自动替换成 WHERE a = 'b'<br />
     * 此外语句中如果已经有WHERE时，(where)的替换开头会自动变成 AND `a` = 'b'<br />
     * <p>$value==NULL时,field会自动循环数组</p>
     *
     * @param string $field
     * @param mixed $value
     * @return $this
     */
    public function addWhere($field, $value) {
        if ($value === NULL && is_array($field)) {
            foreach ($field as $k => $v) {
                $this->_args['WHERE'][$k] = $v;
            }
        
            return $this;
        }
        
        $this->_args['WHERE'][$field] = $value;
        return $this;
    }

    /**
     * 注意和addWhere不同，调用的是parse的parseWhere的方法
     * 即支持AND/OR的方法【必须写嗯】
     *
     * @param array $data
     * @return $this
     */
    public function setWheres($data) {
        $this->_args['WHERES'] = $data;
        return $this;
    }

    /**
     * 设置SQL的LIMIT
     * 传入[0, 10] => LIMIT 0, 10  传入10 => LIMIT 10
     *
     * @param int|array|PageHelper $limit [skip, limit] or limit
     * @return $this
     */
    public function setLimit($limit) {
        if ($limit instanceof PageHelper) {
            $limit = [$limit->getStart(), $limit->getLength()];
        }
        
        $this->_args['LIMIT'] = $this->parser->parseLimit($limit);
        return $this;
    }

    /**
     * 类似WHERE，会替换SQL语句中的(data)
     * 会替换成`field1` = 'value1', `field2` = 'value2'
     *
     * @param string $field
     * @param string|int $value
     * @return $this
     */
    public function addData($field, $value) {
        $this->_args['DATA'][$field] = $value;
        return $this;
    }

    /**
     * 参考addData，只是一次性设定DATA的值
     * 传入一个数组
     *
     * @param array $data
     * @return $this
     */
    public function setData($data) {
        $this->_args['DATA'] = $data;
        return $this;
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
            $sql = str_ireplace('(where)', $parser->parseWhere($this->_args['WHERES']), $sql);
        }
        
        if (!empty($this->_args['WHERE'])) {
            $where = $parser->parseData($this->_args['WHERE']);

            if(!empty($where)){
                // 注意找的是where空格 不是(where)
                $replace = (stripos($sql, " where ")===FALSE ? " WHERE" : " AND")." ".$where;
                $sql = str_ireplace('(where)', $replace, $sql);
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
     * @param \PDO $pdo
     * @return \PDOStatement
     * @throws DBAgentException
     */
    public function getDoc(\PDO $pdo) {
        $sql = $this->getParsedSQL();

        try {
            $this->stmt = $pdo->prepare($sql);
        } catch (\PDOException $e) {
            throw new DBAgentException(
                'SQL prepared error [' . $e->getCode() . '], 
                Message: ' . $e->getMessage() . '. 
                SQL: ' . $sql . PHP_EOL . ' With PDO Message:' . $pdo->errorInfo()[2]);
        }

        if (!empty($this->_bind)) {
            foreach ($this->_bind as $key => $value) {
                $this->stmt->bindValue($key, $value);
            }
        }

        return $this->stmt;
    }

}