<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/1/21
 * Time: 上午10:02
 */

namespace Akari\system\event;

/**
 * Class StopEventBubbling
 * Event的回调抛出本异常时，事件不会继续执行
 *
 * @package Akari\system\event
 */
class StopEventBubbling extends \Exception{

    public function __construct() {

    }

}
