<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-27
 * Time: 18:50
 */

namespace Akari\system\security\helper;


use Akari\Core;
use Akari\exception\AkariException;
use Akari\system\security\cipher\BaseCipher;

class EncryptUtil {

    protected static $cipherInstances = [];

    /**
     * 获得加密实例
     *
     * @param string $mode Config中encrypt的设置名
     * @param bool $newInstance 强制创建新实例
     * @return BaseCipher
     * @throws AkariException
     */
    public static function getCipher($mode = 'default', $newInstance = FALSE) {
        if (isset(self::$cipherInstances[$mode]) && !$newInstance) {
            return self::$cipherInstances[$mode];
        }

        $config = Core::env('encrypt') ?? [];
        if (!array_key_exists($mode, $config)) {
            throw new AkariException("not found cipher config: " . $mode);
        }

        $options = $config[$mode];

        $cipher = $options['cipher'];
        $cipherOpts = isset($options['options']) ? $options['options'] : [];

        /** @var BaseCipher $instance */
        $instance = new $cipher($cipherOpts);
        self::$cipherInstances[$mode] = $instance;

        return $instance;
    }

    /**
     * @param $text
     * @param string $mode
     * @return mixed
     * @throws AkariException
     */
    public static function encrypt($text, $mode = 'default') {
        return self::getCipher($mode)->encrypt($text);
    }

    /**
     * @param $text
     * @param string $mode
     * @return mixed
     * @throws AkariException
     */
    public static function decrypt($text, $mode = 'default') {
        return self::getCipher($mode)->decrypt($text);
    }

}
