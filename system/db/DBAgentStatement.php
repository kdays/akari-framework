<?php
namespace Akari\system\db;

use \PDO;

Class DBAgentStatement {

    protected $pdo;
    protected $SQL;
	protected $_parsedSQL;

    /**
     * @var $stmt \PDOStatement
     */
    public $stmt;
    private $parser;

    private $_bind = Array();
    private $_args = Array();

    const PARAM_BOOL = PDO::PARAM_BOOL;
    const PARAM_INT = PDO::PARAM_INT;
    const PARAM_STMT = PDO::PARAM_STMT;
    const PARAM_STR = PDO::PARAM_STR;
    const PARAM_NULL = PDO::PARAM_NULL;
    const PARAM_LOB = PDO::PARAM_LOB;

    public function __construct($sql, DBAgent $agentObj) {
        $this->pdo = $agentObj->getPDOInstance();
        $this->SQL = $sql;
        $this->parser = DBParser::getInstance($this->pdo);
    }

    public function close() {
        $this->stmt->closeCursor();
	    $this->_parsedSQL = NULL;
        $this->_bind = [];
        $this->_args = [];
    }

	public function addSQL($str) {
		$this->SQL .= $str;
	}

    public function bindValue($key, $value) {
        $this->_bind[':'. $key] = $value;
    }

    public function addOrder($order) {
        $this->_args['ORDER'][] = $order;
    }

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

    public function setLimit($limit) {
        $this->_args['LIMIT'] = $this->parser->parseLimit($limit);
    }

    public function addData($field, $value) {
        $this->_args['DATA'][$field] = $value;
    }

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
	            $replace = (stripos($sql, " where ")===FALSE ? " WHERE" : " AND")." $where";
                $sql = str_replace('(where)', $replace, $sql);
            }
        }
        $sql = str_replace('(where)', '', $sql);

        if (!empty($this->_args['DATA'])) {
            $data = [];
            foreach ($this->_args['DATA'] as $key => $value) {
                $data[] = "`$key`=".$this->pdo->quote($value);
            }

            $sql = str_replace("(data)", implode(",", $data), $sql);
        }
        $sql = str_replace("(data)", "", $sql);

        if (!empty($this->_args['ORDER'])) {
            $sql .= " ORDER BY ".implode(",", $this->_args['ORDER']);
        }

        if (!empty($this->_args['LIMIT'])) {
            $sql .= $this->_args['LIMIT'];
        }

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
            foreach ($this->_bind as $key => $value) {
                $sql = str_replace($key, $value, $sql);
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