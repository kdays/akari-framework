<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:28
 */

namespace Akari\system\util;


class AppValueMgr {

    protected static $values = [];

    public static function get(string $key, $defaultValue = NULL) {
        return static::$values[$key] ?? $defaultValue;
    }

    public static function set(string $key, $value) {
        static::$values[$key] = $value;
    }

    public function has(string $key) {
        return array_key_exists($key, static::$values);
    }

}