<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:09
 */

namespace Akari\system\console;

Class Request {

    protected $params;
    protected $input;
    
    protected function __construct() {
        $params = [];
        if (isset($_SERVER['argv'])) {
            $params = $_SERVER['argv'];
            array_shift($params);
        }

        $this->params = $this->resolve($params);
        $this->input = new ConsoleInput();
    }


    private static $instance = null;
    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new self();
        }

        return self::$instance;
    }


    public function getQuery($key, $defaultValue = NULL) {
        if (empty($key)) {
            return $this->params;
        }
        
        return isset($this->params[$key]) ? $this->params[$key] : $defaultValue;
    }
    
    public function input($message) {
        if (!empty($message) ) {
            Response::getInstance()->message($message);
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