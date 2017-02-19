<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/1/4
 * Time: 09:30.
 */

namespace Akari\system\cache;

use Akari\utility\Benchmark;

class CacheBenchmark
{
    const HIT = 'CACHE.HIT';
    const MISS = 'CACHE.MISS';

    // 操作行为
    const ACTION_CREATE = 'CACHE.INSERT';
    const ACTION_REMOVE = 'CACHE.REMOVE';

    public static function log($event)
    {
        Benchmark::logCount($event, 1);
    }
}
