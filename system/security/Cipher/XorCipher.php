<?php
namespace Akari\system\security\Cipher;

use Akari\Context;

Class XorCipher extends Cipher{
	private $secretKey;

	public static function getInstance($mode = 'default'){
		return self::_instance($mode);
	}

	protected function __construct($mode){
		$this->secretKey = md5(Context::$appConfig->encryptionKey);
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