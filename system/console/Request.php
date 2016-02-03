<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:09
 */

namespace Akari\system\console;

use Akari\system\ioc\DIHelper;

Class Request {

    use DIHelper;
    
    protected $params;
    protected $input;
    
    public function __construct() {
        $params = [];
        if (isset($_SERVER['argv'])) {
            $params = $_SERVER['argv'];
            array_shift($params);
        }

        $this->params = $this->resolve($params);
        $this->input = new ConsoleInput();
    }

    public function getQuery($key, $defaultValue = NULL) {
        if (empty($key)) {
            return $this->params;
        }
        
        return isset($this->params[$key]) ? $this->params[$key] : $defaultValue;
    }
    
    public function input($message) {
        if (!empty($message) ) {
            /** @var Response $resp */
            $resp = $this->_getDI()->getShared("response");
            $resp->message($message);
        }
        return $this->input->read();
    }

    private function resolve(array $params) {
        $now = [];
        foreach ($params as $param) {
            if (preg_match('/^--(\w+)(=(.*))?$/', $param, $matches)) {
                $name = $matches[1];
                $now[$name] = isset($matches[3]) ? $matches[3] : true;
            } else {
                $now[] = $param;
            }
        }

        return $now;
    }

}