<?php

namespace Akari\system\util;

use Akari\system\db\SQLBuilder;
use Akari\system\cache\handler\ICacheHandler;

class AkariDebugUtil {

    protected static $sqls = [];
    protected static $caches = [];

    public static function pushSqlBuilder(SQLBuilder $builder, $query, array $map) {
        if (APP_DEBUG == 1 && !CLI_MODE) {
            $track = debug_backtrace();
            self::$sqls[] = [
                'sql' => $builder->generate($query, $map),
                'file' => $track[4]['file'] ?? $track[3]['file'],
                'line' => $track[4]['line'] ?? $track[3]['line']
            ];
        }
    }

    public static function pushCacheFetch(ICacheHandler $handler, string $key, $isMiss) {
        self::$caches[] = [
            'handler' => get_class($handler),
            'key' => $key,
            'miss' => $isMiss
        ];
    }

    public static function getCacheResult() {
        return self::$caches;
    }

    public static function getSqlResult() {
        return self::$sqls;
    }

}
