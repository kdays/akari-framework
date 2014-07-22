<?php
namespace Akari\system\security\Cipher;

!defined("AKARI_PATH") && exit;

Class PwCipher extends Cipher{
	public static function getInstance(){
		if (self::$d == null) {
			self::$d = new self();
		}
		return self::$d;
	}

	public function encrypt($str){
		$code = '';
		$keylen = strlen($this->secretKey);
		$strlen = strlen($str);
		
		for ($i=0;$i<$strlen;$i++) {
			$k		= $i % $keylen;
			$code  .= $str[$i] ^ $key[$k];
		}
		
		return base64_encode($code);
	}
	
	public function decrypt($str){
		$code = '';
		$str = base64_decode($str);
		$keylen = strlen($this->secretKey);
		$strlen = strlen($str);
		
		for ($i=0;$i<$strlen;$i++) {
			$k		= $i % $keylen;
			$code  .= $str[$i] ^ $key[$k];
		}
		
		return $code;
	}
}