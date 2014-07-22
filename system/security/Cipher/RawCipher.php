<?php
namespace Akari\system\security\Cipher;

/**
 * WARNING: 这不是一个加密函数
 **/
!defined("AKARI_PATH") && exit;

Class RawCipher extends Cipher{
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