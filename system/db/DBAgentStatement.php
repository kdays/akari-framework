<?php
namespace Akari\system\db;

use \PDO;

Class DBAgentStatement{
	protected $pdo;
	protected $parser;

	public $sql;
	public $stmt;
	public $bind = array();
	public $arg = array();
	
	const PARAM_BOOL = PDO::PARAM_BOOL;
    const PARAM_INT = PDO::PARAM_INT;
    const PARAM_STMT = PDO::PARAM_STMT;
    const PARAM_STR = PDO::PARAM_STR;
    const PARAM_NULL = PDO::PARAM_NULL;
    const PARAM_LOB = PDO::PARAM_LOB;

	public function __construct($SQL = '', DBAgent $DBAgent){
		$this->sql = $SQL;
		$this->pdo = $DBAgent->getPDOInstance();
		$this->parser = new DBParser();
	}

	public function close(){
		$this->stmt->closeCursor();
		$this->arg = array();
		$this->bind = array();
	}

	public function getDoc($action = 'select'){
		$sql = $this->sql;

		if(!empty($this->arg['where'])){
			$where = array();

			foreach($this->arg['where'] as $key => $value){
				$where[] = "`$key`=".$this->pdo->quote($value);
			}

			if(!empty($where)){
				$sql = str_replace('(where)', "WHERE ".implode(" AND ", $where), $sql);
			}
		}
		$sql = str_replace('(where)', '', $sql);


		if(!empty($this->arg['data'])){
			$data = array();

			foreach($this->arg['data'] as $key => $value){
				if(is_array($value)){
					$data[] = "`$key` IN (".implode(",", $this->pdo->quote($value)).")";
				}else{
					$data[] = "`$key`=". $this->pdo->quote($value);
				}
			}

			if(!empty($data)){
				$sql = str_replace('(data)', implode(",", $data), $sql);
			}
		}
		$sql = str_replace('(data)', '', $sql);

		if(isset($this->arg['limit'])){
			$sql .= $this->arg['limit'];
		}

		try {
			$this->stmt = $this->pdo->prepare($sql);
		}catch (Exception $e) {
			throw new Exception('SQL prepared error [' . $e->getCode() . '], Message: ' . $e->getMessage() . '. SQL: ' . $sql . PHP_EOL . ' With PDO Message:' . $this->pdo->errorInfo()[2]);
		}

		if(!empty($this->bind)){
			foreach($this->bind as $key => $value){
				$this->stmt->bindValue($key, $value);
			}
		}


		return $this->stmt;
	}

	public function getSQLDebug(){
		$msg = $this->sql;
		if(!empty($this->bind)){
			$msg .= " with param ";
			foreach($this->bind as $key=>$value){
				$msg .= " key `$key` as $value";
			}
		}

		return $msg;
	}

	public function bindValue($key, $data){
		$this->bind[':'.$key] = $data;
	}

	public function addWhere($key, $value){
		$this->arg['where'][$key] = $value;
	}

	public function addData($key, $value){
		$this->arg['data'][$key] = $value;
	}

    public function setData($data) {
        $this->arg['data'] = $data;
    }

	public function addLimit($limit, $size = false){
		$this->arg['limit'] = $size ? " LIMIT $limit,$size" : " LIMIT $limit";
	}
}