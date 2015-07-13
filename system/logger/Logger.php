<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/1
 * Time: 下午11:26
 */

namespace Akari\system\logger;


use Akari\Context;

class Logger {

    public static function log($msg, $level = AKARI_LOG_LEVEL_DEBUG, $strLevel = FALSE){
        $config = Context::$appConfig;
        $logs = array();
        $strLevels = Array(
            AKARI_LOG_LEVEL_DEBUG => "DEBUG",
            AKARI_LOG_LEVEL_INFO => "INFO",
            AKARI_LOG_LEVEL_WARN => "WARNING",
            AKARI_LOG_LEVEL_ERROR => "ERROR",
            AKARI_LOG_LEVEL_FATAL => "FATAL"
        );

        foreach ($config->logs as $idx => $log) {
            if(array_key_exists("enabled", $log)){
                if(!$log['enabled']) {
                    continue;
                }
            }

            if(array_key_exists("url", $log)){
                if(!preg_match($log['url'], Context::$uri))	{
                    continue;
                }
            }

            if (array_key_exists("action", $log)) {
                if (!in_array($log['action'], Context::$appSpecAction)) {
                    continue;
                }
            }

            $logs[] = $log;
        }

        if(!$strLevel) {
            $strLevel = $strLevels[$level];
        }

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

    /**
     * Convert any simple object or array to text
     * @param object $obj
     * @return string
     */
    protected static function _dumpObj($obj) {
        if (is_object($obj)) {
            return '[' . get_class($obj) . ']';
        } elseif (is_array($obj)) {
            return '[Array]';
        } else {
            return $obj;
        }
    }

}