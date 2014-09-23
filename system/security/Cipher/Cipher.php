<?php
namespace Akari\system\security\Cipher;

use Akari\Context;

abstract class Cipher{
	protected static $d = null;
	protected $secretKey = '';
	
   	abstract public function encrypt($str);
	abstract  public function decrypt($str);

	public function setSecretKey($key = NULL) {
		if ($key == NULL){
			$this->secretKey = md5(Context::$appConfig->encryptionKey);
		} else {
			$this->secretKey = $key;
		}
	}
} 