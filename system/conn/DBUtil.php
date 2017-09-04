<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 17/2/15
 * Time: 下午12:02
 */

namespace Akari\system\conn;

use Akari\utility\Benchmark;

class DBUtil {

    public static function mergeMetaKeys($keys, DBConnection $DBConnection) {
        $result = [];
        foreach ($keys as $key) {
            $result[] = $DBConnection->getMetaKey($key) . "  = :$key";
        }

        return implode(",", $result);
    }

    public static function makeLimit($limit) {
        return is_array($limit) ? 
            ' LIMIT ' . $limit[0] . "," . $limit[1] : 
            ' LIMIT ' . $limit;
    }

    public static function getInKeysCond($values, $prefix = 'ALB_') {
        $keys = [];
        foreach ($values as $k => $val) {
            $keys[] = ":" . $prefix . $k;
        }

        return implode(",", $keys);
    }

    public static function getInKeysBindValues($values, $prefix = 'ALB_') {
        $result = [];
        foreach ($values as $k => $val) {
            $result[ $prefix . $k ] = $val;
        }

        return $result;
    }

    public static function beginBenchmark() {
        Benchmark::setTimer(DBConnection::BENCHMARK_KEY);
    }

    public static function endBenchmark($sql) {
        Benchmark::logParams(DBConnection::BENCHMARK_KEY, [
            'time' => Benchmark::getTimerDiff(DBConnection::BENCHMARK_KEY),
            'sql' => $sql
        ]);
    }

}
