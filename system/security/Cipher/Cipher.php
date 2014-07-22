<?php
namespace Akari\system\security\Cipher;

!defined("AKARI_PATH") && exit;

class Cipher{
	protected static $d = null;
	protected $secretKey = '';
	
   	public function encrypt($str){}
	public function decrypt($str){}
	
	public function setKey($key){
		$this->secret_key = $key;
	}
	
	public static function hex2bin($hexdata) {
		$bindata = '';
		$length = strlen($hexdata); 
		for ($i=0; $i < $length; $i += 2){
			$bindata .= chr(hexdec(substr($hexdata, $i, 2)));
		}
		return $bindata;
	}

	public static function pkcs5_pad($text, $blocksize = FALSE){
		if(!$blocksize){
			$blocksize = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		}
		$pad = $blocksize - (strlen($text) % $blocksize);
		return $text . str_repeat(chr($pad), $pad);
	}
 
	public static function pkcs5_unpad($text){
		$pad = ord($text{strlen($text) - 1});
		if ($pad > strlen($text)) return false;
		if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;
		return substr($text, 0, -1 * $pad);
	}
} 