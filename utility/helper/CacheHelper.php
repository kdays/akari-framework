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
use Akari\system\cache\CacheBenchmark;

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
        
        CacheBenchmark::log(CacheBenchmark::MISS);
        
        $result = $failBack();
        $cache->set($key, $result, $timeout);
        return $result;
    }
    
    public static function _deleteCache($key) {
        $cache = self::_getCache();
        return $cache->remove($key);
    }
    
}