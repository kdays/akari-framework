<?php
Class DBAgent{
	public $lastInsertId = false;
	protected $pdo;
	protected $options;
	protected $parser;

	public $arg;

	public function __construct($opts){
		$this->options = $opts;
		$this->parser = DBParser::getInstance($this->getPDOInstance());
	}

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
	 * 查询
	 * @param DBAgentStatement|String $SQL
	 * @param NULL|Array $params
	 **/
	public function query($SQL, $params = NULL){
		$pdo = $this->getPDOInstance();
		if(is_object($SQL) && $SQL instanceof DBAgentStatement){
			$st = $SQL;
		}else{
			$st = $this->createStatementByArr($SQL, $params);
		}

		$s = $st->getDoc();
		$result = $s->execute();

		if($result){
			$rs = $s->fetchAll(PDO::FETCH_ASSOC);
		}else{
			list($errCode, $driverCode, $errMsg) = $s->errorInfo();
			throw new DBAgentException("[Akari.DBAgent] Query Error: $errMsg ($errCode) with SQL: ".$st->getSQLDebug());
		}

		$st->close();

		return $rs;
	}

	public function getOne($SQL, $params = NULL, $class = NULL){
		$pdo = $this->getPDOInstance();
		if(is_object($SQL) && $SQL instanceof DBAgentStatement){
			$st = $SQL;
		}else{
			$st = $this->createStatementByArr($SQL, $params);
		}

		$s = $st->getDoc();
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

		$st->close();

		return $rs;
	}

	public function execute($SQL, $params = array()){
		$pdo = $this->getPDOInstance();
		if(is_object($SQL) && $SQL instanceof DBAgentStatement){
			$st = $SQL;
		}else{
			$st = $this->createStatementByArr($SQL, $params);
		}

		$s = $st->getDoc();
		$result = $s->execute();

		if($result){
			$this->lastInsertId = $pdo->lastInsertId();
		}else{
			list($errCode, $driverCode, $errMsg) = $s->errorInfo();
			throw new DBAgentException("[Akari.DBAgent] Query Error: $errMsg ($errCode) with SQL: ".$st->getSQLDebug());
		}

		$s->close();

		return $s->rowCount();
	}

	public function insertId(){
		return $this->lastInsertId;
	}

	public function prepare($SQL){
		return new DBAgentStatement($SQL, $this);
	}

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
			$field = $this->merge($this->arg['field']);
		}else{
			$field = '*';
		}
		$sql = str_replace('%FIELD%', $this->parser->parseField($field), $sql);
		$sql = str_replace('%TABLE%', $this->arg['table'], $sql);

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

		$data = array();
		$td = $this->parser->parseData($this->arg['data']);
		foreach($td as $key => $value){
			$data[] = "`$key`=$value";
		}

		$sql = str_replace('%DATA%', implode(",", $data));
		if(!empty($this->arg['where'])){
			$sql = str_replace('%WHERE%', $this->parser->parseWhere($this->merge($this->arg['where'])), $sql);
		}

		$this->arg = array();
		return $this->execute($sql);
	}

	public function insert($data = NULL, $table = NULL){
		if($table != NULL)	$this->table($table);
		if($data != NULL)	$this->data($data);

		$sql = 'INSERT INTO %TABLE% SET %DATA%';
		$sql = str_replace('%TABLE%', $this->arg['table'], $sql);

		$data = array();
		$td = $this->parser->parseData($this->arg['data']);
		foreach($td as $key => $value){
			$data[] = "`$key`=$value";
		}

		$sql = str_replace('%DATA%', implode(",", $data));

		$this->arg = array();
		return $this->execute($sql);
	}

	public function field($name){
		$this->arg['select'] = $name;
		return $this;
	}

	public function table($table){
		$this->arg['table'] = $table;
		return $this;
	}

	public function where($options){
		$this->arg['where'][] = $options;
		return $this;
	}

	public function join($table){
		$this->arg['join'][] = $table;
		return $this;
	}

	public function order($order){
		$this->arg['order'] = $order;
		return $this;
	}

	public function limit($limit){
		$this->arg['limit'] = $limit;
		return $this;
	}

	public function group($group){
		$this->arg['group'] = $group;
		return $this;
	}

	public function having($having){
		$this->arg['having'] = $having;
		return $this;
	}

	public function data($data){
		$this->arg['data'][] = $data;
		return $this;
	}
}

Class DBAgentException extends Exception{

}