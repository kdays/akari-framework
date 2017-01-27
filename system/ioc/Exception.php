<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 17/1/25
 * Time: 下午4:56
 */

namespace Akari\system\ioc;


use Akari\system\exception\AkariException;

class Exception extends AkariException {

    public function __construct($msg) {
        $this->message = "[Akari.Inject] " . $msg; 
    }

}