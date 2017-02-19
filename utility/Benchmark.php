<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:55.
 */

namespace Akari\utility;

class Benchmark
{
    const F_MISS = 'MISS';
    const F_HIT = 'HIT';

    public static $counter = [];
    public static $params = [];

    public static function logCount($event, $count = 1)
    {
        $event = strtoupper($event);

        if (!isset(self::$counter[$event])) {
            self::$counter[$event] = 0;
        }

        self::$counter[$event] += $count;
    }

    public static function logParams($event, $params = [])
    {
        if (CLI_MODE) {
            return;
        }

        $event = strtoupper($event);

        if (!isset(self::$params[$event])) {
            self::$params[$event] = [];
        }

        self::$params[$event][] = $params;
    }

    public static function setTimer($event, $getValue = false)
    {
        static $timer = [];

        if ($getValue) {
            $value = $timer[$event];
            unset($timer[$event]);

            return $value;
        }

        $timer[$event] = microtime(true);
    }

    // 设置setTimer后再调用getTimerDiff设置相同的event 会获得时间差
    public static function getTimerDiff($event)
    {
        $lastTimer = self::setTimer($event, true);
        if (!$lastTimer) {
            return false;
        }

        return microtime(true) - $lastTimer;
    }
}
