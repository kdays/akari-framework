<?php
!defined("AKARI_PATH") && exit;

Class Security{
	/**
	 * 获得Cipher实例
	 * 
	 * @param string $type 类型
	 * @throws Exception
	 * @return mixed
	 */
	public static function getCipherInstance($type){
		if($type == NULL)	$type = Context::$appConfig->encryptCipher;
		$clsName = in_string($type, "Cipher") ? $type : $type."Cipher";
		if(!class_exists($clsName)){
			throw new Exception("[akari.Security] Cipher $type not found");
		}

		return call_user_func_array(Array($clsName, "getInstance"), Array());
	}
	
	/**
	 * 获得CSRF的token
	 * 
	 * @param string $key 设置FALSE时使用CSRF_KEY的值
	 * @return string
	 */
	public static function getCSRFToken($key = FALSE){
		if(!$key && defined("CSRF_KEY"))	$key = CSRF_KEY;
		return substr( md5(Context::$appConfig->encryptionKey."_".$key."_".$_SERVER['HTTP_USER_AGENT'].$_SERVER['REMOTE_ADDR']) ,7, 9);
	}
	
	/**
	 * 检查CSRF的token是否正常
	 * 
	 * @param string $key csrf-key，false时自动获得
	 * @param string $token token，为空时自动获得
	 * @throws Exception
	 */
	public static function verifyCSRFToken($key = FALSE, $token = ''){
		$tokenName = Context::$appConfig->csrfTokenName;
		if($token == '')	$token = $_REQUEST[$tokenName];
		if(!$key && defined("CSRF_KEY"))	$key = CSRF_KEY;

		if($token != self::getCSRFToken($key)){
			throw new Exception("[akari.Security] Forbidden. CSRF VerifyErr");
		}
	}
	
	/**
	 * 加密字符串
	 * 
	 * @param string $str 待加密的内容
	 * @param string $type 加密方式
	 */
	public static function encrypt($str, $type = NULL){
		return self::getCipherInstance($type)->encrypt($str);
	}
	
	/**
	 * 解密字符串
	 * 
	 * @param string $str 加密的内容
	 * @param string $type 解密方式
	 */
	public static function decrypt($str, $type = NULL){
		return self::getCipherInstance($type)->decrypt($str);
	}
}