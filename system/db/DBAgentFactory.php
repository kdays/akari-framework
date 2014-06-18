<?php
Class DBAgentFactory{
	private static $instance = array();
	/**
	 * 获得DBAgent对象
	 * 
	 * @param string $cfgName 配置名
	 * @return multitype:
	 */
	public static function getDBAgent($cfgName = "default"){
		$config = Context::$appConfig->getDBConfig($cfgName);
		
		if(!array_key_exists($cfgName, self::$instance)){
			self::$instance[$cfgName] = new DBAgent($config);
 		}

 		return self::$instance[$cfgName];
	}
}