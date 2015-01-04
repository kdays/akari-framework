<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:10
 */

namespace Akari\utility\helper;

use Akari\Context;

trait Logging{
    public static $strLevel = Array(
        AKARI_LOG_LEVEL_DEBUG => "DEBUG",
        AKARI_LOG_LEVEL_INFO => "INFO",
        AKARI_LOG_LEVEL_WARN => "WARNING",
        AKARI_LOG_LEVEL_ERROR => "ERROR",
        AKARI_LOG_LEVEL_FATAL => "FATAL"
    );

    public static function _log($msg, $level = AKARI_LOG_LEVEL_DEBUG, $strLevel = FALSE){
        $config = Context::$appConfig;
        $logs = array();

        foreach ($config->logs as $idx => $log) {
            if(array_key_exists("enabled", $log)){
                if(!$log['enabled'])	continue;
            }

            if(array_key_exists("url", $log)){
                if(!preg_match($log['url'], Context::$uri))	continue;
            }

            $logs[] = $log;
        }

        if(!$strLevel)	$strLevel = self::$strLevel[$level];

        $toLower = [
            'DEBUG' => 'DBG',
            'INFO' => 'INF',
            'WARNING' => 'WRN',
            'ERROR' => 'ERR',
            'FATAL' => 'FAT'
        ];

        foreach($logs as $log){
            $logLevel = $log['level'];
            if($level & $logLevel){
                $appender = $log['appender']::getInstance($log['params']);
                $appender->append(
                    '[' . $toLower[$strLevel] . '] ' .
                    self::_dumpObj($msg), $strLevel);
            }
        }
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

    /**
     * Convert any simple object or array to text
     * @param object $obj
     * @return string
     */
    protected static function _dumpObj($obj) {
        if (is_object($obj) || is_array($obj)) {
            $text = print_r($obj, true);
            $text = preg_replace('/\s+/', " ", $text);
            return $text;
        } else {
            return $obj;
        }
    }
}