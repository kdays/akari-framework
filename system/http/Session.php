<?php
!defined("AKARI_PATH") && exit;

Class Session{
	private static $s = null;
	public static function getInstance(){
		if(self::$s == null){
			self::$s = new self();
		}
		
		return self::$s;
	}

	protected function __construct(){
		session_start();
	}

	public function set($key, $value){
		$_SESSION[$key] = $value;
	}

	public function remove($key){
		unset($_SESSION[$key]);
	}

	public function get($key = NULL, $defaultValue = NULL){
		if($key == NULL)	return $_SESSION;

		if(isset($_SESSION[$key])){
			return $_SESSION[$key];
		}

		return $defaultValue;
	}
}