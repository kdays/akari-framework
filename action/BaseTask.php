<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/31
 * Time: 10:42
 */

namespace Akari\action;

use Akari\system\console\Request;
use Akari\system\console\Response;
use Akari\system\ioc\DIHelper;
use Akari\utility\helper\ExceptionSetter;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ValueHelper;

Class BaseTask {

    use Logging, ValueHelper, ExceptionSetter, DIHelper;

    protected $request;

    protected $response;

    public function __construct() {
        $this->request = Request::getInstance();
        $this->response = Response::getInstance();
    }
    
    protected function message($text, $style = NULL) {
        return $this->response->message($text, $style);
    }
    
}