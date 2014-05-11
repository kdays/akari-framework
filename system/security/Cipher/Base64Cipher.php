<?php
!defined("AKARI_PATH") && exit;

Class Base64Cipher extends Cipher{
	public static function getInstance(){
		if (self::$d == null) {
            self::$d = new self();
        }
        return self::$d;
	}

	public function encrypt($str){
		return base64_encode($str);
	}
	
	public function decrypt($str){
		return base64_decode($str);
	}
}