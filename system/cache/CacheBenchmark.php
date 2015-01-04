<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/1/4
 * Time: 09:30
 */

namespace Akari\system\cache;

use Akari\utility\Benchmark;

Class CacheBenchmark {

    const HIT = "CACHE.". Benchmark::F_HIT;
    const MISS = "CACHE.". Benchmark::F_MISS;

    // 操作行为
    const ACTION_CREATE = "CACHE.INSERT";
    const ACTION_REMOVE = "CACHE.REMOVE";

    public static function log($event) {
        Benchmark::logCount($event, 1);
    }

}