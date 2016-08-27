<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/4
 * Time: 下午2:24
 */

namespace Akari\utility\helper;

use Akari\Context;
use Akari\system\cache\Cache;

trait CacheHelper {

    /**
     * @param string $key
     * @return Cache
     */
    public static function _getCache($key = 'default') {
        return Cache::getInstance($key);
    }

    public static function _fetchCache($key, callable $failBack, $timeout = NULL) {
        $cache = self::_getCache();

        if ($cache->exists($key)) {
            return $cache->get($key);
        }
        
        $result = $failBack();
        $cache->set($key, $result, $timeout);
        return $result;
    }
    
}