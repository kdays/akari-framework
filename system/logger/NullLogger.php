<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/3/18
 * Time: 下午11:42
 */

namespace Akari\system\logger;


class NullLogger {

    public static $l;
    public static function getInstance(array $opt){
        if (self::$l == null) {
            self::$l = new self($opt);
        }
        return self::$l;
    }

    public function append($msg, $level) {
        // none...
    }

}