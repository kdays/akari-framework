<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/4
 * Time: 下午2:24
 */

namespace Akari\system\cache;


trait CacheHelper {

    /**
     * @param string $key
     * @return Cache
     */
    public static function _getCache($key = 'default') {
        return Cache::getInstance($key);
    }
    
}