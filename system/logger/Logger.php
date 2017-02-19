<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/9/16
 * Time: 下午9:27.
 */

namespace Akari\system\logger;

use Akari\system\ioc\DIHelper;

class Logger
{
    use DIHelper;

    public static $levelStrs = [
        AKARI_LOG_LEVEL_DEBUG => 'debug',
        AKARI_LOG_LEVEL_INFO  => 'info',
        AKARI_LOG_LEVEL_ERROR => 'error',
        AKARI_LOG_LEVEL_WARN  => 'warning',
        AKARI_LOG_LEVEL_FATAL => 'emergency',
    ];

    public static function log($message, $level = AKARI_LOG_LEVEL_INFO)
    {
        /** @var \Akari\system\logger\handler\ILoggerHandler $logger */
        $logger = self::_getDI()->getShared('logger');

        if ($logger) {
            $logger->append(self::_dumpObj($message), $level);
        }

        return $logger;
    }

    /**
     * Convert any simple object or array to text.
     *
     * @param mixed $arg
     *
     * @return string
     */
    protected static function _dumpObj($arg)
    {
        if (is_object($arg)) {
            return '['.get_class($arg).']';
        } elseif (is_array($arg)) {
            return '[Array]';
        } elseif (is_null($arg)) {
            return 'NULL';
        } elseif (is_bool($arg)) {
            return $arg ? 'TRUE' : 'FALSE';
        } elseif (is_resource($arg)) {
            return get_resource_type($arg);
        }

        return $arg;
    }
}
