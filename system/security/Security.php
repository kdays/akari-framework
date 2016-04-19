<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:07
 */

namespace Akari\system\security;

use Akari\Context;
use Akari\system\http\Request;
use Akari\system\ioc\DIHelper;
use Akari\system\security\cipher\Cipher;
use Akari\utility\helper\ValueHelper;

Class Security {
	
    const KEY_TOKEN = "Security:CSRF";
    
	use ValueHelper, DIHelper;
	
    /**
     * 获得CSRF的token
     *
     * @return string
     */
	public static function getCSRFToken(){
		/** @var Request $request */
		$request = self::_getDI()->getShared("request");
		$key = self::_getValue(self::KEY_TOKEN, NULL, $request->getUserIP());

		$str = "CSRF-". Context::$appConfig->appName. $key;
		return substr(md5($str) ,7, 9);
	}

    /**
     * 检查CSRF的token是否正常
     *
     * @throws CSRFVerifyFailed
     */
	public static function verifyCSRFToken(){
		$tokenName = Context::$appConfig->csrfTokenName;
		$token = NULL;
		
		if (!empty($_COOKIE[$tokenName])) {
			$token = $_COOKIE[$tokenName];
		} elseif (!empty($_REQUEST[$tokenName])) {
			$token = $_REQUEST[$tokenName];
		}

		if($token != self::getCSRFToken()){
			throw new CSRFVerifyFailed();
		}
	}
	
	public static function autoVerifyCSRFToken() {
		$config = Context::$appConfig;
		$needVerify = (!CLI_MODE && $config->autoPostTokenCheck);
		
		if (!empty($config->csrfTokenName) && $needVerify) {
			$tokenValue = self::getCSRFToken();
			
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				setcookie($config->csrfTokenName, $tokenValue, $config->cookiePath, $config->cookieDomain);
			} else {
				self::verifyCSRFToken();
			}
		}
	}
	
	protected static $cipherInstances = [];

	/**
	 * 获得加密实例
	 *
	 * @param string $mode Config中encrypt的设置名
	 * @param bool $newInstance 强制创建新实例
	 * @return Cipher
	 * @throws NotFoundCipherMode
	 */
	public static function getCipher($mode = 'default', $newInstance = false) {
		if (isset(self::$cipherInstances[$mode]) && !$newInstance) {
			return self::$cipherInstances[$mode];
		}

		$config = Context::$appConfig->encrypt;
		if (!array_key_exists($mode, $config)) {
			throw new NotFoundCipherMode("not found cipher config: ". $mode);
		}
		
		$options = $config[$mode];

		$cipher = $options['cipher'];
		$cipherOpts = isset($options['options']) ? $options['options'] : [];
		
		/** @var Cipher $instance */
		$instance = new $cipher($cipherOpts);
		self::$cipherInstances[$mode] = $instance;
		
		return $instance;
	}

	/**
	 * @param $text
	 * @param string $mode
	 * @return mixed
	 */
	public static function encrypt($text, $mode = 'default') {
		return self::getCipher($mode)->encrypt($text);
	}

	/**
	 * @param $text
	 * @param string $mode
	 * @return mixed
	 */
	public static function decrypt($text, $mode = 'default') {
		return self::getCipher($mode)->decrypt($text);
	}
}

Class CSRFVerifyFailed extends \Exception {

    public function __construct() {
        $this->message = "[Akari.Security]
            表单验证失败，请返回上一页刷新重新提交试试。
            如果多次失败可以尝试更换游览器再行提交。
            (POST Security Token Verify Failed)";
    }

}

Class NotFoundCipherMode extends \Exception {

}