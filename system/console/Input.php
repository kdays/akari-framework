<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:09
 */

namespace Akari\system\console;

Class Input {

    protected $input;
    protected $parameters = [];

    protected static function resolve() {
        $parameters = [];
        if (array_key_exists("argv", $_SERVER)) {
            $parameters = $_SERVER['argv'];
            array_shift($parameters);
        }

        function resolve(array $params) {
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

        return resolve($parameters);
    }
    
    public function __construct($handle = 'php://stdin') {
        $this->input = fopen($handle, 'r');
        $this->parameters = self::resolve();
    }
    
    public function __destruct() {
        // TODO: Implement __destruct() method.
        if ($this->input) {
            fclose($this->input);
        }
    }

    public function getInput() {
        return fgets($this->input);
    }
    
    public function hasParameter($key) {
        return !!array_key_exists($key, $this->parameters);
    }

    public function getParameter($key, $defaultValue = NULL) {
        if (empty($key)) {
            return $this->parameters;
        }

        return isset($this->parameters[$key]) ? $this->parameters[$key] : $defaultValue;
    }


}