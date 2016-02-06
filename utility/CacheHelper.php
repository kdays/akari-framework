<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/25
 * Time: 下午7:15
 */

namespace Akari\utility;


use Akari\Context;
use Akari\system\cache\Cache;

class CacheHelper {

    public static function get($key, callable $failback, $timeout = NULL) {
        $cache = Cache::getInstance();
        
        if ($cache->exists($key) && Context::$mode != 'Dev') {
            return $cache->get($key);
        }
        
        $result = $failback();
        $cache->set($key, $result, $timeout);
        return $result;
    }
    
}