<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:10
 */

namespace Akari\utility\helper;

use Akari\system\logger\Logger;

trait Logging{

    protected static function _log($msg, $level = AKARI_LOG_LEVEL_DEBUG) {
        Logger::log($msg, $level);
    }

    protected static function _logDebug($msg) {
        self::_log($msg, AKARI_LOG_LEVEL_DEBUG);
    }

    protected static function _logInfo($msg) {
        self::_log($msg, AKARI_LOG_LEVEL_INFO);
    }

    protected static function _logWarn($msg) {
        self::_log($msg, AKARI_LOG_LEVEL_WARN);
    }

    protected static function _logErr($msg) {
        self::_log($msg, AKARI_LOG_LEVEL_ERROR);
    }

    protected static function _logFatal($msg) {
        self::_log($msg, AKARI_LOG_LEVEL_FATAL);
    }
}
