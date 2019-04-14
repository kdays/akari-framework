<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/19
 * Time: 21:26
 */

namespace Akari\system\storage;


use Akari\Core;
use Akari\exception\AkariException;

class Storage {

    const KEY_DEFAULT = '.default';

    /**
     * @param string $configName
     * @return StorageDisk
     * @throws AkariException
     */
    public static function disk(string $configName) {
        static $handlers = [];
        if (!isset($handlers[$configName])) {
            $config = Core::env('storage', [])[$configName] ?? [];
            if (empty($config)) {
                throw new AkariException("Storage not exists: ". $configName);
            }
            $handler = new $config['handler']($config);

            $handlers[$configName] = new StorageDisk($handler);
        }

        return $handlers[$configName];
    }

    public static function put(string $path, $content) {
        return self::disk(self::KEY_DEFAULT)->put($path, $content);
    }

    public static function get(string $path) {
        return self::disk(self::KEY_DEFAULT)->get($path);
    }


}
