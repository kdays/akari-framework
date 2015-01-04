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

Class Security {

    /**
     * 获得CSRF的token
     *
     * @param bool|string $key 设置FALSE时使用CSRF_KEY的值
     * @return string
     */
	public static function getCSRFToken($key = FALSE){
		if(!$key && defined("CSRF_KEY"))	$key = CSRF_KEY;

		$req = Request::getInstance();
		$str = implode("_", [
            Context::$appConfig->appName,
			$key,
			$req->getUserAgent(),
			$req->getUserIP()
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

	/**
	 * @param $type
	 * @param string $mode
	 * @param array $config
	 * @return cipher\Cipher
	 */
	public static function getCipher($type, $mode = 'default', $config = []) {
		$cls = implode(NAMESPACE_SEPARATOR, ["Akari", "system", "security", "cipher", ucfirst($type)."Cipher"]);
		if (!empty($config)) {
			Context::$appConfig->encrypt[ $mode ] = array_merge($config, ['ciper' => ucfirst($type)]);
		}

		return $cls::getInstance($mode);
	}

	public static function encrypt($text, $mode = 'default') {
		$cipher = Context::$appConfig->encrypt[ $mode ]['cipher'];
		$instance = self::getCipher($cipher, $mode);

		return $instance->encrypt($text);
	}

	public static function decrypt($text, $mode = 'default') {
		$cipher = Context::$appConfig->encrypt[ $mode ]['cipher'];
		$instance = self::getCipher($cipher, $mode);

		return $instance->decrypt($text);
	}
}

Class CSRFVerifyFailed extends \Exception {

    public function __construct() {
        $this->message = "[Akari.Security] Verify CSRF Token Failed";
    }

}