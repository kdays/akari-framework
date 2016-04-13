<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:29
 */

namespace Akari\action;

use Akari\system\cache\CacheHelper;
use Akari\system\http\Request;
use Akari\system\http\Response;
use Akari\system\ioc\DIHelper;
use Akari\system\ioc\Injectable;
use Akari\utility\helper\ExceptionSetter;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\TemplateViewHelper;
use Akari\utility\helper\ValueHelper;

abstract class BaseAction extends Injectable{

    use Logging, ValueHelper, ResultHelper, ExceptionSetter, DIHelper, CacheHelper;

    public function __construct() {
        
    }

}