<?php
namespace Akari\system\security\Cipher;

/**
 * WARNING: 这不是一个加密函数
 **/
Class Base64Cipher extends Cipher{

	public static function getInstance($mode = 'default'){
		return self::_instance($mode);
	}

	public function encrypt($str){
		return base64_encode($str);
	}
	
	public function decrypt($str){
		return base64_decode($str);
	}
}