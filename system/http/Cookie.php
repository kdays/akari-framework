<?php
namespace Akari\system\http;

use Akari\Context;
use Akari\system\security\Security;

!defined("AKARI_PATH") && exit;

Class Cookie{

	const FLAG_ENCRYPT = "|ENC";
	const FLAG_ARRAY = "|A";
	const SPLIT = "\x00";

	protected static $c;
	public static function getInstance() {
		if (self::$c == NULL){
			self::$c = new self();
		}
		return self::$c;
	}
	
	/**
	 * 设置Cookie
	 * 
	 * @param string $name Cookie的名称
	 * @param mixed $value 数据，可以传入简单数组
	 * @param string $expire 有效时间，没有设定则以设置中的cookieTime为准
	 * @param boolean $isEncrypt 是否进行加密
	 * @param array $option 可选参数(path, domain)
	 * @todo value为FALSE时是删除，也可用Cookie::remove操作
	 */
	public function set($name, $value, $expire = NULL, $isEncrypt = FALSE, $option = array()){
		$config = Context::$appConfig;

		$expire = isset($expire) ? $expire : $config->cookieTime;
		if(is_numeric($expire)){
			$expire += time();
		}else{
			$expire = $expire=='now' ? 0 : strtotime($expire);
		}

		if(is_array($value)){
			$tmp = array();
			foreach($value as $k => $v) $tmp[] = "$k".self::SPLIT."$v";
			$value = implode("&", $tmp).self::FLAG_ARRAY;
		}

		if($isEncrypt){
			$value = Security::encrypt($value, 'Cookie', $config->cookieEncrypt).self::FLAG_ENCRYPT;
		}

		$path = array_key_exists("path", $option) ? $option['path'] : $config->cookiePath;
		$domain  = array_key_exists("domain", $option) ? $option['domain'] : $config->cookieDomain;

		if($config->cookiePrefix)   $name = $config->cookiePrefix.$name;

		if($value === FALSE){
			setCookie($name, '', time() - 3600, $path, $domain);
		}else{
			setCookie($name, $value, $expire, $path, $domain);
		}
	}

    /**
     * 获得Cookie的值
     *
     * @param string $name 键名
     * @param bool|string $encryptType 加密方式 不设定时按照设置检查
     * @param bool|string $autoPrefix 是否自动添加prefix，false时程序不会添加prefix取值
     * @return NULL|string|Array
     * @todo 加密和数组在取值时会自动处理，不必额外设定
     */
	public function get($name, $encryptType = FALSE, $autoPrefix = true){
		$config = Context::$appConfig;

		if(!in_string($name, $config->cookiePrefix) && $autoPrefix){
			$name = $config->cookiePrefix.$name;
		}

		if(!array_key_exists($name, $_COOKIE)) return NULL;
		$cookie = $_COOKIE[$name];

		// 处理加密解密时Cipher key可能丢失的问题
		if($encryptType != FALSE)   $cookie = Security::decrypt($cookie, 'Cookie', $encryptType);

		$flag = self::FLAG_ENCRYPT;
		$fLen = strlen($flag);

		if(substr($cookie, -$fLen, $fLen) == $flag){
			$cookie = substr($cookie, 0, -$fLen);
			$cookie = Security::decrypt($cookie, 'Cookie', $config->cookieEncrypt);
		}

		//@todo: 如果最后2位是|A 代表是数组 
		if(substr($cookie, -2, 2) == self::FLAG_ARRAY){
			$result = array();
			$arrayFlagLen = strlen(self::FLAG_ARRAY) + 1;
			$value = explode("&", substr($cookie, 0, sizeof($cookie) - $arrayFlagLen));
			foreach($value as $v){
				list($key, $val) = explode(self::SPLIT, $v);
				$result[ $key ] = $val;
			}

			return $result;
		}

		return $cookie;
	}
	
	/**
	 * 删除Cookie
	 * 
	 * @param string $key 键名
	 */
	public function remove($key){
		$this->set($key, FALSE);
	}
}