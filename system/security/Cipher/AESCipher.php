<?php
namespace Akari\system\security\Cipher;

use Akari\Context;

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


	public function hex2bin($hex) {
		$bin = '';
		$length = strlen($hex);
		for ($i = 0; $i < $length; $i += 2){
			$bin .= chr(hexdec(substr($hex, $i, 2)));
		}
		return $bin;
	}

	public function pkcs5_pad($text, $blockSize = FALSE){
		if(!$blockSize){
			$blockSize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		}
		$pad = $blockSize - (strlen($text) % $blockSize);
		return $text . str_repeat(chr($pad), $pad);
	}

	public function pkcs5_unpad($text){
		$pad = ord($text{strlen($text) - 1});
		if ($pad > strlen($text)) return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
		return substr($text, 0, -1 * $pad);
	}
}