<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/9/16
 * Time: 下午9:28
 */

namespace Akari\system\logger\handler;


interface ILoggerHandler {
    
    public function append($message, $level);
    public function getHandler();
    public function setOption($key, $value);
    public function setLevel($level);
    
}