<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/9/16
 * Time: 下午9:51
 */

namespace Akari\system\logger\handler;

use \SeasLog;


class SeasLoggerHandler implements ILoggerHandler{
    
    protected $levels = [
        SEASLOG_DEBUG => AKARI_LOG_LEVEL_DEBUG,
        SEASLOG_INFO => AKARI_LOG_LEVEL_INFO,
        SEASLOG_NOTICE => AKARI_LOG_LEVEL_INFO,
        SEASLOG_WARNING => AKARI_LOG_LEVEL_WARN,
        SEASLOG_ERROR => AKARI_LOG_LEVEL_ERROR,
        SEASLOG_CRITICAL => AKARI_LOG_LEVEL_ERROR,
        SEASLOG_ALERT => AKARI_LOG_LEVEL_FATAL,
        SEASLOG_EMERGENCY => AKARI_LOG_LEVEL_FATAL
    ];
    protected $maxLogLevel;
    
    public function append($message, $level) {
        if (!($level & $this->maxLogLevel)) {
            return NULL;
        }
        return call_user_func_array(['\SeasLog', $this->levels[$level]], [$message]);
    }
    
    public function setOption($key, $value) {
        
    }
    
    public function getHandler() {
        return new SeasLog();
    }
    
    public function setLevel($level) {
        
    }

}