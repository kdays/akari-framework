<?php
!defined("AKARI_PATH") && exit;

class Cipher{
	protected static $d = null;
	protected $secretKey = '';
	public $padMethod = null;
	
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
	
	public function padOrUnpad($str, $ext){
		if (is_null($this->pad_method))	return $str;
		$func_name = __CLASS__ . '::' . $this->pad_method . '_' . $ext . 'pad';
		if ( is_callable($func_name) ){
			$size = mcrypt_get_block_size($this->cipher, $this->mode);
			return call_user_func($func_name, $str, $size);
		}
		
		return $str;
	}
	
	protected function pad($str){
		return $this->padOrUnpad($str, ''); 
	}
 
	protected function unpad($str){
		return $this->padOrUnpad($str, 'un'); 
	}

	public static function pkcs5_pad($text, $blocksize){
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