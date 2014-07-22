<?php
namespace Akari\system\http;

!defined("AKARI_PATH") && exit;

Class MobileDevice{
	private $userAgent;

	public function __contruct(){
		$this->userAgent = $_SERVER['HTTP_USER_AGENT'];
	}

	protected static $m;
	public static function getInstance(){
		if(!isset(self::$m)){
			self::$m = new self();
		}
		return self::$m;
	}
	
	public function isIPhone(){
		return (preg_match('/ipod/i', $this->userAgent)||preg_match('/iphone/i', $this->userAgent));
	}

	public function isMobile(){
		if($this->isIPhone())	return true;
		if(preg_match('/android/i',$this->userAgent)){
			return true;
		}

		if(preg_match('/opera mini/i',$this->userAgent)){
			return true;
		}

		if(preg_match('/blackberry/i',$this->userAgent)){
			return true;
		}

		return false;
	}
}