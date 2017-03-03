<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:07
 */

namespace Akari\system\security;

use Akari\Context;
use Akari\system\ioc\DIHelper;
use Akari\utility\helper\ValueHelper;
use Akari\system\http\VerifyCsrfToken;
use Akari\system\security\cipher\Cipher;
use Akari\system\exception\AkariException;

class Security {

    const KEY_TOKEN = "Security:CSRF";

	use ValueHelper, DIHelper;

    /**
     * 获得CSRF的token
     *
     * @return string
     */
	public static function getCSRFToken() {
		/** @var VerifyCsrfToken $verifier */
		$verifier = self::_getDI()->getShared('csrf');

		return $verifier->getToken();
	}

    /**
     * 检查CSRF的token是否正常
     *
     */
	public static function verifyCSRFToken() {
		/** @var VerifyCsrfToken $verifier */
		$verifier = self::_getDI()->getShared('csrf');
		$verifier->verifyToken();
	}

	public static function autoVerifyCSRFToken() {
		/** @var VerifyCsrfToken $verifier */
		$verifier = self::_getDI()->getShared('csrf');
		$verifier->autoVerify();
	}

	protected static $cipherInstances = [];

    /**
     * 获得加密实例
     *
     * @param string $mode Config中encrypt的设置名
     * @param bool $newInstance 强制创建新实例
     * @return Cipher
     * @throws AkariException
     */
	public static function getCipher($mode = 'default', $newInstance = FALSE) {
		if (isset(self::$cipherInstances[$mode]) && !$newInstance) {
			return self::$cipherInstances[$mode];
		}

		$config = Context::$appConfig->encrypt;
		if (!array_key_exists($mode, $config)) {
			throw new AkariException("not found cipher config: " . $mode);
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
