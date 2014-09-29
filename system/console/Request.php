<?php
namespace Akari\system\console;

Class Request {
    private $_params;
    private $_cached = [];

    protected static $r;
    public static function getInstance() {
        if (!isset(self::$r)) {
            self::$r = new self();
            self::$r->resolve();
        }

        return self::$r;
    }

    public function getParam($key, $defaultValue = NULL) {
        return array_key_exists($key, $this->_cached) ? $this->_cached[$key] : $defaultValue;
    }

    public function getParams() {
        if (!isset($this->_params)) {
            if (isset($_SERVER['argv'])) {
                $this->_params = $_SERVER['argv'];
                array_shift($this->_params);
            } else {
                $this->_params = [];
            }
        }

        return $this->_params;
    }

    private function resolve() {
        $params = $this->getParams();

        $now = [];
        foreach ($params as $param) {
            if (preg_match('/^--(\w+)(=(.*))?$/', $param, $matches)) {
                $name = $matches[1];
                $now[$name] = isset($matches[3]) ? $matches[3] : true;
            } else {
                $now[] = $param;
            }
        }

        $this->_cached = $now;
    }
}