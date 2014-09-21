<?php
namespace Akari\utility;

Class BenchmarkHelper {

    const FLAG_MISS = "miss";
    const FLAG_HIT = "hit";

    /**
     * @return array
     */
    public static function getMemoryUsage() {
        return [
            "usage" => memory_get_usage(),
            "peak" => memory_get_peak_usage()
        ];
    }

    /**
     * @param string $type hit or miss
     */
    public static function setCacheHit($type) {
        logcount("cache_".$type, 1);
    }

    /**
     * @return array
     */
    public static function getCacheHit() {
        return [
            "hit" => logcount("cache_hit", NULL),
            "miss" => logcount("cache_miss", NULL)
        ];
    }

    /**
     * @param $point
     */
    public static function setTimer( $point ) {
        static $pointes = [];

        if (empty($point)){
            return $pointes;
        }

        $pointes[ $point ] = microtime(true);
    }

    /**
     * @param $SQL
     * @param string $action start or end
     * @param array $trace
     * @param array $params
     * @return array
     */
    public static function setSQLTimer($SQL, $action = 'start', $trace = [], $params = []) {
        static $pointes = [];

        if (empty($SQL)){
            return $pointes;
        }

        $pointes[ ] = [
            "t" => microtime(true),
            "sql" => $SQL,
            "trace" => $trace,
            "params" => $params,
            "a" => $action
        ];
    }

    /**
     * @return int
     */
    public static function getQueryCount() {
        return logcount("db.query", NULL);
    }

    public static function getSQLBenchmark() {
        $sql = self::setSQLTimer(NULL);
        $sqlTime = 0;
        $lastSQLStr = "";

        $result = [];
        foreach ($sql as $value) {
            if ($sqlTime > 0 && $value['sql'] == $lastSQLStr) {
                $result[] = [
                    "sql" => $value['sql'],
                    "ms" => round(($value['t'] - $sqlTime) * 1000, 2),
                    "trace" => implode("\n", $value['trace']),
                    "param" => $value['params']
                ];
            }

            $lastSQLStr = $value['sql'];
            $sqlTime = $value['t'];
        }

        return $result;
    }

    public static function getFrameworkBenchmark() {
        $timerList = self::setTimer(NULL);
        $lastTime = 0;
        $lastTimes = [];
        foreach ($timerList as $key => $value) {
            if ($lastTime > 0) {
                $lastTimes[$key] = round(($value - $lastTime) * 1000, 2);
            }

            $lastTime = $value;
        }

        return $lastTimes;
    }
}