<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:29
 */

namespace Akari\action;

use Akari\system\http\HttpHelper;
use Akari\system\http\Request;
use Akari\system\http\Response;
use Akari\system\ioc\DIHelper;
use Akari\utility\helper\ExceptionSetter;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\TemplateViewHelper;
use Akari\utility\helper\ValueHelper;

abstract class BaseAction {

    use Logging, ValueHelper, ResultHelper, ExceptionSetter, HttpHelper, TemplateViewHelper, DIHelper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;
    
    public function __construct() {
        $this->request = $this->_getDI()->getShared('request');
        $this->response = $this->_getDI()->getShared('response');
    }

}