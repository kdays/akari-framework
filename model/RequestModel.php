<?php

Class RequestModel extends Model{
	public function __construct(){
		return $this->values();
	}

	public function values(){
		foreach($this as $key => $value){
			$req = GP($key, $this->_getMethod($key));
			if($req)	$this->$key = $this->_checkParams($key, $req);
		}

		return $this;
	}

	public function _checkParams($key, $reqValue){
		return $reqValue;
	}

	public function _getMethod($key){
		return 'GP';
	}
}