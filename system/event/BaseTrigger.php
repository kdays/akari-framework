<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 09:18
 */

namespace Akari\system\event;

use Akari\system\http\Request;
use Akari\system\http\Response;
use Akari\system\ioc\DIHelper;
use Akari\system\ioc\Injectable;
use Akari\system\tpl\TemplateHelper;
use Akari\utility\helper\ExceptionSetter;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\ValueHelper;

abstract Class BaseTrigger extends Injectable{

    use ResultHelper, Logging, ExceptionSetter, ValueHelper;

    protected function stop() {
        throw new StopEventBubbling();
    }

}