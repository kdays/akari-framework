<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:32
 */

namespace Akari\utility\helper;

use Akari\system\exception\ExceptionProcessor;

trait ExceptionSetter {
    
    public static function _setExceptionHandler($cls) {
        /** @var ExceptionProcessor $processor */
        $processor = ExceptionProcessor::getInstance();
        $processor->setHandler($cls);
    }

}