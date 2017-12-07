<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/12/7
 * Time: 下午6:30
 */

namespace Akari\system\storage;


use Akari\Context;

class Storage {

    /**
     * @param string $configName
     * @return StorageDisk
     */
    public static function disk(string $configName) {
        static $handlers = [];
        if (!isset($handlers[$configName])) {
            $config = Context::$appConfig->storage[$configName];
            $handler = new $config['handler']($config);

            $handlers[$configName] = new StorageDisk($handler);
        }

        return $handlers[$configName];
    }

    public static function put(string $path, $content) {
        return self::disk('default')->put($path, $content);
    }

    public static function get(string $path) {
        return self::disk('default')->get($path);
    }


}