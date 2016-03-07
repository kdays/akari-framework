<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 09:18
 */

namespace Akari\system\event;

use Akari\system\http\Request;
use Akari\system\ioc\DIHelper;
use Akari\utility\helper\ExceptionSetter;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\ValueHelper;

abstract Class BaseTrigger {

    use ResultHelper, Logging, ExceptionSetter, ValueHelper, DIHelper;

    /** @var  Request */
    public $request;
    
    public function __construct() {
        $this->request = $this->_getDI()->getShared("request");
    }

    protected function stop() {
        throw new StopEventBubbling();
    }

}