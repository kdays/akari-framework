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

    /**
     * 异常设置
     * 
     * @param $cls
     * @param bool $setFkHandler 是否将框架【所有】异常全部指向这个handler
     * 设置后dispatcher的404和fatalException这些也会交给handler处理
     */
    public static function _setExceptionHandler($cls, $setFkHandler = FALSE) {
        /** @var ExceptionProcessor $processor */
        $processor = ExceptionProcessor::getInstance();
        $processor->setHandler($cls, $setFkHandler);
    }

}
