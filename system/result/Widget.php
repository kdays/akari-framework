<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/5/19
 * Time: 下午10:04
 */

namespace Akari\system\result;

use Akari\system\http\Request;
use Akari\system\http\Response;
use Akari\system\ioc\DIHelper;
use Akari\system\ioc\Injectable;
use Akari\system\tpl\TemplateHelper;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\ValueHelper;

abstract class Widget extends Injectable{

    use ValueHelper, Logging;

    /**
     * @param mixed $userData
     * @return array
     */
    abstract public function execute($userData = NULL);

}