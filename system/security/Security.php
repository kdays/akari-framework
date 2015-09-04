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
	
	use ValueHelper;
	
    /**
     * 获得CSRF的token
     *
     * @param bool|string $key 设置FALSE时使用CSRF_KEY的值
     * @return string
     */
	public static function getCSRFToken($key = FALSE){
		$request = Request::getInstance();
		if (!$key) {
			$key = self::_getValue(
				"Security:CSRF", FALSE, $request->getUserIP()
			);
		}

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
     * @param bool|string $key csrf-key，false时自动获得
     * @param string $token token，为空时自动获得
     * @throws CSRFVerifyFailed
     */
	public static function verifyCSRFToken($key = FALSE, $token = ''){
		$tokenName = Context::$appConfig->csrfTokenName;
		if($token == '')	$token = $_REQUEST[$tokenName];
		if(!$key && defined("CSRF_KEY"))	$key = CSRF_KEY;

		if($token != self::getCSRFToken($key)){
			throw new CSRFVerifyFailed();
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