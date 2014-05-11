<?php
!defined("AKARI_PATH") && exit;

Class Security{
	public function getCipherInstance($type){
		if($type == NULL)	$type = Context::$appConfig->encryptCipher;
		$clsName = in_string($type, "Cipher") ? $type : $type."Cipher";
		if(!class_exists($clsName)){
			throw new Exception("[akari.Security] Cipher $type not found");
		}

		return call_user_func_array(Array($clsName, "getInstance"), Array());
	}

	public function getCSRFToken($key = FALSE){
		if(!$key && defined("CSRF_KEY"))	$key = CSRF_KEY;
		return substr( md5(Context::$appConfig->encryptionKey."_".$key."_".$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']) ,7, 9);
	}

	public function verifyCSRFToken($key = FALSE, $token = ''){
		$tokenName = Context::$appConfig->csrfTokenName;
		if($token == '')	$token = $_REQUEST[$tokenName];
		if(!$key && defined("CSRF_KEY"))	$key = CSRF_KEY;

		if($token != self::getCSRFToken($key)){
			throw new Exception("[akari.Security] Forbidden. CSRF VerifyErr");
		}
	}
	
	public function encrypt($str, $type = NULL){
		return self::getCipherInstance($type)->encrypt($str);
	}

	public function decrypt($str, $type = NULL){
		return self::getCipherInstance($type)->decrypt($str);
	}
}