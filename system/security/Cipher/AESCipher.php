<?php
namespace Akari\system\security\Cipher;

use Akari\Context;

!defined("AKARI_PATH") && exit;

Class AESCipher extends Cipher{
	protected $cipher = MCRYPT_RIJNDAEL_128;
	protected $mode   = MCRYPT_MODE_ECB;
	protected $iv	  = '';

	public static function getInstance(){
		if (self::$d == null) {
			self::$d = new self();
		}
		return self::$d;
	}

	protected function __construct($key = NULL){
		$this->iv = Context::$appConfig->cipherIv;
		$this->secretKey = md5(Context::$appConfig->encryptionKey);
	}

	public function setSecretKey($key = NULL) {
		if ($key == NULL){
			$this->secretKey = md5(Context::$appConfig->encryptionKey);
		} else {
			$this->secretKey = $key;
		}
	}

	public function encrypt($str){
		$str = $this->pkcs5_pad($str);
		$td = mcrypt_module_open($this->cipher, '', $this->mode, '');
		
		if(empty($this->iv)){
			$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		}else{
			$iv = $this->iv;
		}

		mcrypt_generic_init($td, $this->secretKey, $iv);
		$cyperText = mcrypt_generic($td, $str);
		$result = bin2hex($cyperText);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);

		return $result;
	}

	public function decrypt($str){
		$td = mcrypt_module_open($this->cipher, '', $this->mode, '');
		if(empty($this->iv)){
			$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
		}else{
			$iv = $this->iv;
		}
		
		mcrypt_generic_init($td, $this->secretKey, $iv);
		$decryptedText = mdecrypt_generic($td, $this->hex2bin($str));
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
 
		return $this->pkcs5_unpad($decryptedText);
	}
}