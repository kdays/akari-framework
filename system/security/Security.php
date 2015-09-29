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
use Akari\utility\helper\ValueHelper;

Class Security {
	
    const KEY_TOKEN = "Security:CSRF";
    
	use ValueHelper;
	
    /**
     * 获得CSRF的token
     *
     * @return string
     */
	public static function getCSRFToken(){
		$request = Request::getInstance();
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
				setCookie(Context::$appConfig->csrfTokenName, $tokenValue);
			} else {
				self::verifyCSRFToken();
			}
		}
	}
	
	public static function encrypt($text, $mode = 'default') {
		$cipher = Context::$appConfig->encrypt[ $mode ]['cipher'];
        $instance = $cipher::getInstance($mode);

		return $instance->encrypt($text);
	}

	public static function decrypt($text, $mode = 'default') {
		$cipher = Context::$appConfig->encrypt[ $mode ]['cipher'];
        $instance = $cipher::getInstance($mode);

		return $instance->decrypt($text);
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