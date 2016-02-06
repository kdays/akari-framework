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
        $this->request = $this->_getDI()->getShared('request');
        $this->response = $this->_getDI()->getShared('response');
    }
    
    protected function message($text, $style = NULL) {
        if (is_array($text)) {
            $r = '';
            foreach ($text as $k => $p) {
                $r .= " [Array] ". $k . " -> ". $p;
            }
            
            $text = $r;
        }
        return $this->response->message($text, $style);
    }
    
}