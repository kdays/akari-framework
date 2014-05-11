<?php
Class DBAgentFactory{
	private static $instance = array();
	public static function getDBAgent($cfgName = "default"){
		$config = Context::$appConfig->getDBConfig($cfgName);
		
		if(!array_key_exists($cfgName, self::$instance)){
			self::$instance[$cfgName] = new DBAgent($config);
 		}

 		return self::$instance[$cfgName];
	}
}