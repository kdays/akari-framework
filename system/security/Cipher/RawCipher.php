<?php
namespace Akari\system\security\Cipher;

/**
 * WARNING: 这不是一个加密函数
 **/
Class RawCipher extends Cipher{

	public static function getInstance($mode = 'default'){
		return self::_instance($mode);
	}

	public function encrypt($str){
		return rawurlencode($str);
	}
	
	public function decrypt($str){
		return rawurldecode($str);
	}
}