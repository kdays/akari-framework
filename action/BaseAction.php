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
use Akari\utility\helper\ExceptionSetter;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\ValueHelper;

Class BaseAction {

    use Logging, ValueHelper, ResultHelper, ExceptionSetter, HttpHelper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    public function __construct() {
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
    }

}