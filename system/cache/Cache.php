<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-25
 * Time: 17:51
 */

namespace Akari\system\cache;



use Akari\Core;
use Akari\exception\AkariException;
use Akari\system\cache\handler\ICacheHandler;

class Cache {

    const KEY_DEFAULT = 'default';
    protected static $_instances = [];

    /**
     * @param string $key
     * @return ICacheHandler|NULL
     * @throws AkariException
     */
    public static function getHandler(string $key) {
        if (array_key_exists($key, self::$_instances)) {
            return self::$_instances[$key];
        }

        $config = Core::env('cache')[$key] ?? [];
        if (empty($config) || empty($config['handler'])) {
            throw new AkariException("cache not exists: ". $key);
        }

        $handler = $config['handler'];
        self::$_instances[$key] = new $handler($config);

        return self::$_instances[$key];
    }

    public static function set(string $key, $value, int $timeout, $raw = FALSE, string $config = self::KEY_DEFAULT) {
        return self::getHandler($config)->set($key, $value, $timeout, $raw);
    }

    public static function get(string $key, $raw = FALSE, string $config = self::KEY_DEFAULT) {
        return self::getHandler($config)->get($key, NULL, $raw);
    }

    public static function exists(string $key, string $config = self::KEY_DEFAULT) {
        return self::getHandler($config)->exists($key);
    }

    public static function remove(string $key, string $config = self::KEY_DEFAULT) {
        return self::getHandler($config)->remove($key);
    }

    public static function increment(string $key, int $value = 1, string $config = self::KEY_DEFAULT) {
        return self::getHandler($config)->increment($key, $value);
    }

    public static function decrement(string $key, int $value, string $config = self::KEY_DEFAULT) {
        return self::getHandler($config)->decrement($key, $value);
    }

}
