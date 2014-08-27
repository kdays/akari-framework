<?php
namespace Akari\system\db;

use Akari\system\Event;
use Akari\utility\BenchmarkHelper;
use Akari\system\log\Logging;
use \PDO;
use \PDOException;

Class DBAgent{
	public $lastInsertId = false;

    /**
     * @var \PDO
     */
    protected $pdo;
	protected $options;
	protected $parser;

    // 开启性能检查后 会记录每句SQL语句的时间
    public $doBenchmark = false;

	public $arg;

	/**
	 * 构造函数
	 *
	 * @param array $opts 对数据库的配置
	 **/
	public function __construct($opts){
		$this->options = $opts;
		$this->parser = DBParser::getInstance($this->getPDOInstance());
	}

    /**
     * 是否开启性能检查
     *
     * @param bool $setTo 开关
     */
    public function setBenchmark($setTo = FALSE) {
        $this->doBenchmark = $setTo;
    }

    /**
     * 性能记录，见setBenchmark
     *
     * @param string $SQL SQL语句
     * @param string $action 操作
     * @param array $params 参数
     */
    public function logBenchmark($SQL, $action = 'start', $params = []) {
        if ($this->doBenchmark) {
            $trace = debug_backtrace();
            $backtrace = [];
            foreach ($trace as $value) {
                $backtrace[] = basename($value['file'])." L".$value['line'];
            }

            BenchmarkHelper::setSQLTimer($SQL, $action, $backtrace, $params);
        }
    }

    /**
     * 获得PDO的单例
     *
     * @throws DBAgentException
     * @return \PDO
     */
	public function getPDOInstance(){
		if(!class_exists("PDO")){
			throw new DBAgentException("[Akari.DB.DBAgent] PDO not installed");
		}

		if($this->pdo == NULL){
			extract($this->options);

			try{
				$this->pdo = new PDO($dsn, $username, $password, $options);
			}catch(PDOException $e){
				Logging::_logErr($e);
				throw new DBAgentException("[Akari.DB.DBAgent] PDO Connect Error. ".$e->getCode()." ".$e->getMessage());
			}
		}
		
		return $this->pdo;
	}

    /**
     * 类似mysql_ping，防止mysql gone way
     *
     * @return \PDO
     */
    public function ping() {
        $status = $this->pdo->getAttribute(PDO::ATTR_SERVER_INFO);

        if ($status == 'MySQL server has gone away') {
            Logging::_logDebug("gone away");
            $this->pdo = NULL;
        }

        return $this->getPDOInstance();
    }

    /**
	 * 查询
	 * 
	 * @param DBAgentStatement|String $SQL sql查询语句
	 * @param NULL|Array $params 绑定的参数
     * @throws DBAgentException
	 * @return array
	 * @todo params在SQL传入DBAgentStatement对象无效
	 **/
	public function query($SQL, $params = NULL){
		logcount("db.query", 1);

		if(is_object($SQL) && $SQL instanceof DBAgentStatement){
			$st = $SQL;
		}else{
			$st = $this->createStatementByArr($SQL, $params);
		}

		$s = $st->getDoc();
        $this->logBenchmark($s->queryString);
		$result = $s->execute();

		if($result){
			$rs = $s->fetchAll(PDO::FETCH_ASSOC);
		}else{
			list($errCode, $driverCode, $errMsg) = $s->errorInfo();
			throw new DBAgentException("[Akari.DBAgent] Query Error: $errMsg ($errCode) with SQL: ".$st->getSQLDebug());
		}

        $this->logBenchmark($s->queryString, 'end', $st->getParam());
		$st->close();

		return $rs;
	}
    
	/**
	 * 获得单个查询
	 * 
	 * @param mixed $SQL 查询对象或语句
	 * @param array $params 参数数组
	 * @param string $class 返回Class
	 * @throws DBAgentException
	 * @return mixed
	 * @todo params在SQL传入DBAgentStatement对象无效
	 */
	public function getOne($SQL, $params = NULL, $class = NULL){
		logcount("db.query", 1);

		if(is_object($SQL) && $SQL instanceof DBAgentStatement){
			$st = $SQL;
		}else{
			$st = $this->createStatementByArr($SQL, $params);
		}

		$s = $st->getDoc();
        $this->logBenchmark($s->queryString);
		$result = $s->execute();


		if($result){
			if($class != NULL){
				$rs = $s->fetchObject($class);
			}else{
				$rs = $s->fetch(PDO::FETCH_ASSOC);
			}
		}else{
			list($errCode, $driverCode, $errMsg) = $s->errorInfo();
			throw new DBAgentException("[Akari.DBAgent] Query Error: $errMsg ($errCode) with SQL: ".$st->getSQLDebug());
		}

        $this->logBenchmark($s->queryString, 'end', $st->getParam());
		$st->close();

		return $rs;
	}

    /**
	 * 执行SQL操作
	 * 
	 * @param mixed $SQL 查询对象或语句
	 * @param array $params 参数数组
	 * @throws DBAgentException
	 * @return int
	 */
	public function execute($SQL, $params = array()){
		logcount("db.query", 1);

		$pdo = $this->getPDOInstance();
		if(is_object($SQL) && $SQL instanceof DBAgentStatement){
			$st = $SQL;
		}else{
			$st = $this->createStatementByArr($SQL, $params);
		}

		$s = $st->getDoc();
        $this->logBenchmark($s->queryString);
		$result = $s->execute();

		if($result){
			$this->lastInsertId = $pdo->lastInsertId();
		}else{
			list($errCode, $driverCode, $errMsg) = $s->errorInfo();
			throw new DBAgentException("[Akari.DBAgent] Query Error: $errMsg ($errCode) with SQL: ".$st->getSQLDebug());
		}

        $this->logBenchmark($s->queryString, 'end', $st->getParam());
		$st->close();

		return $s->rowCount();
	}

	/**
	 * 返回上次执行时的最近一次插入的id
	 * 
	 * @return boolean|int
	 */
	public function insertId(){
		return $this->lastInsertId;
	}
    
	/**
	 * 获得SQL准备对象
	 * 
	 * @param string $SQL SQL语句
	 * @return DBAgentStatement
	 */
	public function prepare($SQL){
		return new DBAgentStatement($SQL, $this);
	}
    
	/**
	 * 根据Arr设定DBStatement对象
	 * 
	 * @param string $SQL sql语句
	 * @param array $params 参数对象
	 * @return DBAgentStatement
	 */
	public function createStatementByArr($SQL, $params = NULL){
		if($params == NULL)	return $this->prepare($SQL);

		$r = $this->prepare($SQL);
		if($params){
			foreach($params as $key => $value){
				$r->bindValue($key, $value);
			}
		}

		return $r;
	}

	/**
	 * 在进行链式ORM操作后的查询
	 * 
	 * @param string $table 表名
	 * @param string $join 是否JOIN查询
	 * @param string $where 是否进行where操作
	 * @return Array|NULL
	 */
	public function select($table = NULL, $join = NULL, $where = NULL){
		if($table != NULL)	$this->table($table);
		if($join != NULL)	$this->join($join);
		if($where != NULL){
			if(array_key_exists('limit', $where)){
				$this->limit($where['limit']);
				unset($where['limit']);
			}

			if(!empty($where))	$this->where($where);
		}

		$sql = 'SELECT%DISTINCT% %FIELD% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%';
		$sql = str_replace('%DISTINCT%', '', $sql);

		//field
		if(!empty($this->arg['field'])){
			$field = $this->arg['field'];
		}else{
			$field = '*';
		}

		$sql = str_replace('%FIELD%', $this->parser->parseField($field), $sql);
		$sql = str_replace('%TABLE%', $this->arg['table'], $sql);

		Event::fire("db.select", [
			"table" => $this->arg['table'],
			"arg" => $this->arg
		]);

		foreach(Array('join', 'where', 'order', 'group', 'having', 'limit') as $item){
			if(!empty($this->arg[$item])){
				if(in_array($item, Array('JOIN', 'WHERE'))){
					$list = array();
					foreach($data as $key => $value){
						if(is_array($value)){
							foreach($value as $k => $v){
								$list[$k] = $v;
							}
						}else{
							$list[$key] = $value;
						}
					}

					$this->arg[$item] = $list;
				}

				$methodName = "parse".ucfirst($item);
				$val = $this->parser->$methodName($this->arg[$item]);
				$sql = str_replace("%".strtoupper($item)."%", $val, $sql);
			}else{
				$sql = str_replace("%".strtoupper($item)."%", '', $sql);
			}
		}

		$this->arg = array();
		return $this->query($sql);
	}
    
	/**
	 * 在进行链式ORM操作后的更新
	 * 
	 * @param string $data 数据
	 * @param string $table 表名
	 * @param string $where 是否进行where操作
	 * @return int
	 */
	public function update($data = NULL, $table = NULL, $where = NULL){
		if($table != NULL)	$this->table($table);
		if($data != NULL)	$this->data($data);
		if($where != NULL){
			if(array_key_exists('limit', $where)){
				$this->limit($where['limit']);
				unset($where['limit']);
			}

			if(!empty($where))	$this->where($where);
		}
		$sql = 'UPDATE %TABLE% SET %DATA% %WHERE%';
		$sql = str_replace('%TABLE%', $this->arg['table'], $sql);

		Event::fire("db.update", [
			"table" => $this->arg['table'],
			"arg" => $this->arg
		]);

		$data = array();
		if(isset($this->arg['data'])){
			$td = $this->arg['data'];
			foreach($td as $key => $value){
				$data[] = "`$key`=".$this->parser->parseValue($value);
			}
		}

		$sql = str_replace('%DATA%', implode(",", $data), $sql);
		if(!empty($this->arg['where'])){
			$sql = str_replace('%WHERE%', $this->parser->parseWhere($this->arg['where']), $sql);
		}

		$this->arg = array();
		return $this->execute($sql);
	}

	/**
	 * 在进行链式ORM操作后的插入
	 *
	 * @param string $data 数据
	 * @param string $table 表名
	 * @return int
	 */
	public function insert($data = NULL, $table = NULL){
		if($table != NULL)	$this->table($table);
		if($data != NULL)	$this->data($data);

		$sql = 'INSERT INTO %TABLE% SET %DATA%';
		$sql = str_replace('%TABLE%', $this->arg['table'], $sql);

		Event::fire("db.insert", [
			"table" => $this->arg['table'],
			"arg" => $this->arg
		]);

		$data = array();
		if(isset($this->arg['data'])){
			$td = $this->arg['data'];
			foreach($td as $key => $value){
				$data[] = "`$key`=".$this->parser->parseValue($value);
			}
		}
		$sql = str_replace('%DATA%', implode(",", $data), $sql);

		$this->arg = array();
		return $this->execute($sql);
	}

	/**
	 * ORM链式操作: 设定查询的范围
	 * 
	 * @param string $name 列名
	 * @return DBAgent
	 */
	public function field($name){
		$this->arg['field'] = $name;
		return $this;
	}
    
	/**
	 * ORM链式操作: 设定表名
	 * 
	 * @param string $table 表名
	 * @return DBAgent
	 */
	public function table($table){
		$this->arg['table'] = $table;
		return $this;
	}

	/**
	 * ORM链式操作: 设定where的范围
	 * 
	 * @param array $options 范围
	 * @return DBAgent
	 */
	public function where($options){
		$this->arg['where'][] = $options;
		return $this;
	}
    
	/**
	 * ORM链式操作: JOIN操作
	 * 
	 * @param string $table 表名
	 * @return DBAgent
	 */
	public function join($table){
		$this->arg['join'][] = $table;
		return $this;
	}
    
	/**
	 * ORM链式操作: Order排序设定
	 * 
	 * @param string $order 排序方式
	 * @return DBAgent
	 */
	public function order($order){
		$this->arg['order'] = $order;
		return $this;
	}
    
	/**
	 * ORM链式操作: limit显示显示数
	 * 
	 * @param mixed $limit
	 * @return DBAgent
	 */
	public function limit($limit){
		$this->arg['limit'] = $limit;
		return $this;
	}

	/**
	 * ORM链式操作: 设定groupby
	 * 
	 * @param string $group
	 * @return DBAgent
	 */
	public function group($group){
		$this->arg['group'] = $group;
		return $this;
	}
    
	/**
	 * ORM链式操作: 设定Having
	 * 
	 * @param array $having
	 * @return DBAgent
	 */
	public function having($having){
		$this->arg['having'] = $having;
		return $this;
	}
    
	/**
	 * ORM链式操作: 设定数据
	 * 
	 * @param array $data
	 * @return DBAgent
	 */
	public function data($data){
		if(!isset($this->arg['data'])){
			$this->arg['data'] = array();
		}

		foreach($data as $key => $value){
			$this->arg['data'][$key] = $value;
		}

		return $this;
	}

    /**
     * Begin PDO transaction
     *
     * @return bool
     */
    public function beginTransaction() {
        $pdo = $this->getPDOInstance();
        $pdo->query('set autocommit = 0');
        return $pdo->beginTransaction();
    }

    /**
     * Commit PDO transaction
     *
     * @return bool
     */
    public function commit() {
        $pdo = $this->getPDOInstance();
        $result = $pdo->commit();
        $pdo->query('set autocommit = 1');
        return $result;
    }

    /**
     * Roolback
     *
     * @return bool
     */
    public function rollback() {
        $pdo = $this->getPDOInstance();
        $result = $pdo->rollBack();
        $pdo->query('set autocommit = 1');
        return $result;
    }
}

Class DBAgentException extends \Exception{

}