<?php
Class DataHelper{
	public static $data = array();

	public function get($key = false, $subKey = false){
		if($key === false){
			return self::$data;
		}else{
			if(!self::$data[$key])	return false;
			if($subKey){
				return array_key_exists($subKey, self::$data[$key]) ? self::$data[$key][$subKey] : false;
			}
			
			return self::$data[$key];
		}
	}

	public function set($key = '', $data = array()){
		if(!isset(self::$data[$key]))	self::$data[$key] = $data;
	}

	public function update($key, $s_key, $value){
		if(!isset(self::$data[$key]))	self::$data[$key] = array();
		self::$data[$key][$s_key] = $value;
	}
	
	public function load($path, $key = ''){
		if($key == '')	$key = array_shift(explode(".", basename($path)));
		
		$sPath = Context::$appBasePath."data/$path";
		if(!file_exists($sPath))	return false;
		if(preg_match('/\.php/i', $path)){
			self::set($key, self::GetFile($sPath));
		}
		
		return true;
	}
	
	public static function GetFile($path){
		return include($path);
	}
}