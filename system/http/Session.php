<?php
namespace Akari\system\http;

use Akari\system\log\Logging;

!defined("AKARI_PATH") && exit;

Class Session{
	public static function init(){
		if(CLI_MODE){
			Logging::_logWarn("Session cannot running on CLI mode");
		}else{
			session_start();
		}
	}

	public static function set($key, $value){
		$_SESSION[$key] = $value;
	}

	public static function remove($key){
		unset($_SESSION[$key]);
	}

	public static function get($key = NULL, $defaultValue = NULL){
		if($key == NULL)	return $_SESSION;

		if(isset($_SESSION[$key])){
			return $_SESSION[$key];
		}

		return $defaultValue;
	}
}