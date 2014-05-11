<?php
!defined("AKARI_PATH") && exit;

Class I18n{
	public static $data = array();
	public static $loaded = array();

	public static function load($name, $prefix = ""){
		if(isset(self::$loaded[$prefix.$name]))	return false;
		$langPath = Context::$appBasePath."/app/language/$name.php";
		if(!file_exists($langPath)){
			throw new Exception("[Akari.I18n] not found [ $prefix $name ]");
		}

		self::$loaded[$prefix.$name] = time();
		$now = self::getlang($langPath);
		foreach($now as $key => $value){
			self::$data[$prefix.$key] = $value;
		}

		return true;
	}

	public static function get($id, $L = Array(), $prefix = ""){
		$id = $prefix.$id;
		$lang = isset(self::$data[$id]) ? self::$data[$id] : "[$id]";
		foreach($L as $key => $value){
			$lang = str_replace("%$key%", $value, $lang);
		}

		return $lang;
	}

	public static function getlang($path){
		return include($path);
	}
}