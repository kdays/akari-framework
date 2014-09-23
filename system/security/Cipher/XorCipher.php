<?php
namespace Akari\system\security\Cipher;

Class XorCipher extends Cipher{
	public static function getInstance(){
		if (self::$d == null) {
			self::$d = new self();
		}
		return self::$d;
	}

	public function encrypt($str){
		$code = '';
		$keyLen = strlen($this->secretKey);
		$strLen = strlen($str);
		
		for ($i = 0;$i < $strLen; $i++) {
			$k		= $i % $keyLen;
			$code  .= $str[$i] ^ $this->secretKey[$k];
		}
		
		return base64_encode($code);
	}
	
	public function decrypt($str){
		$code = '';
		$str = base64_decode($str);
		$keyLen = strlen($this->secretKey);
		$strLen = strlen($str);
		
		for ($i = 0; $i < $strLen; $i++) {
			$k		= $i % $keyLen;
			$code  .= $str[$i] ^ $this->secretKey[$k];
		}
		
		return $code;
	}
}