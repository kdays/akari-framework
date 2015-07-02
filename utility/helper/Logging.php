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

    public static function _log($msg, $level = AKARI_LOG_LEVEL_DEBUG, $strLevel = FALSE){
        Logger::log($msg, $level, $strLevel);
    }

    public static function _logDebug($msg){
        self::_log($msg, AKARI_LOG_LEVEL_DEBUG);
    }

    public static function _logInfo($msg){
        self::_log($msg, AKARI_LOG_LEVEL_INFO);
    }

    public static function _logWarn($msg){
        self::_log($msg, AKARI_LOG_LEVEL_WARN);
    }

    public static function _logErr($msg){
        self::_log($msg, AKARI_LOG_LEVEL_ERROR);
    }

    public static function _logFatal($msg){
        self::_log($msg, AKARI_LOG_LEVEL_FATAL);
    }
}