<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/4/18
 * Time: 下午4:10
 */

namespace Akari\utility;


class ResourceMgr {
    
    protected static $lists = [];
    
    public static function push($path) {
        self::$lists[] = $path;
    }

}