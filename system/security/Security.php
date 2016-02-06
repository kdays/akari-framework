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
		$key = self::_getValue(self::KEY_TOKEN, false, $request->getUserIP());

		$str = implode("_", [
            Context::$appConfig->appName,
			$key,
			$request->getUserAgent()
		]);

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
		if (!empty(Context::$appConfig->csrfTokenName) && !CLI_MODE) {
			$tokenValue = self::getCSRFToken();
			if ($_SERVER['REQUEST_METHOD'] != 'POST') {
				setCookie(Context::$appConfig->csrfTokenName, $tokenValue, NULL, Context::$appConfig->cookiePath);
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
	 */
	public static function getCipher($mode = 'default', $newInstance = false) {
		if (isset(self::$cipherInstances[$mode]) && !$newInstance) {
			return self::$cipherInstances[$mode];
		}

		$options = Context::$appConfig->encrypt[$mode];

		$cipher = $options['cipher'];
		
		/** @var Cipher $instance */
		$instance = new $cipher($options['options']);
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