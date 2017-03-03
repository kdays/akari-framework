<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/31
 * Time: 上午7:54
 */

namespace Akari\system\conn;

use Akari\Context;

class DBConnFactory {

    private static $instance = array();

    /**
     * 获得DBAgent对象
     *
     * @param string $cfgName 配置名
     * @return DBConnection
     */
    public static function get($cfgName = "default") {
        $config = Context::$appConfig->getDBConfig($cfgName);

        if(!array_key_exists($cfgName, self::$instance)){
            self::$instance[$cfgName] = new DBConnection($config);
        }

        return self::$instance[$cfgName];
    }


}
