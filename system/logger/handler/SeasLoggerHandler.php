<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/9/16
 * Time: 下午9:51.
 */

namespace Akari\system\logger\handler;

use SeasLog;

class SeasLoggerHandler implements ILoggerHandler
{
    protected $levels = [
        AKARI_LOG_LEVEL_DEBUG => SEASLOG_DEBUG,
        AKARI_LOG_LEVEL_INFO  => SEASLOG_INFO,
        AKARI_LOG_LEVEL_WARN  => SEASLOG_WARNING,
        AKARI_LOG_LEVEL_ERROR => SEASLOG_ERROR,
        AKARI_LOG_LEVEL_FATAL => SEASLOG_EMERGENCY,
    ];
    protected $maxLogLevel = AKARI_LOG_LEVEL_ALL;

    public function append($message, $level)
    {
        if (!($level & $this->maxLogLevel)) {
            return;
        }

        return SeasLog::log($this->levels[$level], $message);
    }

    public function setOption($key, $value)
    {
    }

    public function getHandler()
    {
        return new SeasLog();
    }

    public function setLevel($level)
    {
        $this->maxLogLevel = $level;
    }
}
