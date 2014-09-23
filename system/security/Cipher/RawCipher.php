<?php
namespace Akari\system\security\Cipher;

/**
 * WARNING: 这不是一个加密函数
 **/
Class RawCipher extends Cipher{

	protected static $d = null;

	public static function getInstance(){
		if (self::$d == null) {
			self::$d = new self();
		}
		return self::$d;
	}

	public function encrypt($str){
		return rawurlencode($str);
	}
	
	public function decrypt($str){
		return rawurldecode($str);
	}
}