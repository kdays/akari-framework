<?php
!defined("AKARI_PATH") && exit;

Class DatabaseModel extends Model{
	public $db;
	public $table;

	public function __construct(){
		$this->db = DBAgentFactory::getDBAgent();
	}

	public function create($data = array()){
		$arr = array();
		$tblName = $this->table;

		$sql = "INSERT INTO `$tblName` SET ";
		$sqlArr = array();
		foreach($this as $key => $value){
			if($key == "db" || $key == "table")	continue;
			if(array_key_exists($key, $data)){
				$value = $data[$key];
			}

			$arr[$key] = $this->_checkParams($key, $value);
			$sqlArr[]  = "$key=:$key";
		}
		$sql .= implode($sqlArr, ",");

		$db = $this->db;
		$r = $db->update($sql, $arr);
	}

	public function _checkParams($key, $value){
		return $value;
	}
}