<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/30
 * Time: 下午6:40
 */

namespace Akari\system\conn;


abstract class BaseSQLMap {

    public $table;
    
    public $lists;

    protected static $m;

    /**
     * 单例公共调用，不应在action中调用本方法
     * @return static
     */
    public static function given() {
        $class = get_called_class();
        if (!isset(self::$m[$class])) {
            self::$m[ $class ] = new $class;
        }

        return self::$m[ $class ];
    }
    
}