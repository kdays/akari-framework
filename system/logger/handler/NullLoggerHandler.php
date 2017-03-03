<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/9/16
 * Time: 下午9:41
 */

namespace Akari\system\logger\handler;

class NullLoggerHandler implements ILoggerHandler{

    public function getHandler() {

    }

    public function append($message, $level) {

    }

    public function setLevel($level) {

    }

    public function setOption($key, $value) {

    }

}
