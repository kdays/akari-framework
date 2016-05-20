<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/9/16
 * Time: 下午9:32
 */

namespace Akari\system\logger\handler;


use Akari\system\logger\Logger;
use Akari\utility\FileHelper;

class FileLoggerHandler implements ILoggerHandler{
    
    protected $handler;
    protected $opts;
    protected $level = AKARI_LOG_LEVEL_PRODUCTION;

    public function getHandler() {
        $logPath = $this->opts['path'];
        if (!$this->handler) {
            if (!file_exists($logPath)) {
                FileHelper::createDir(dirname($logPath));
            }
            
            @$this->handler = fopen($logPath, 'a');
            @chmod($logPath, 0777);
        }

        return $this->handler;
    }
    
    public function setOption($key, $value) {
        $this->opts[$key] = $value;
    }
    
    public function append($message, $level) {
        if (!($level & $this->level)) {
            return ;
        }
        $handler = $this->getHandler();

        @flock($handler, LOCK_EX);
        @fwrite($handler, date('[Y-m-d H:i:s] ['). Logger::$levelStrs[$level]. "] " . $message . "\n");
        @flock($handler, LOCK_UN);
    }
    
    public function setLevel($level) {
        $this->level = $level;
    }
    
}