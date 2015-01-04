<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:09
 */

namespace Akari\system\console;

Class Request {

    protected static $r;
    public static function getInstance() {
        if (!isset(self::$r)) {
            self::$r = new self();
        }

        return self::$r;
    }

    protected function __construct() {

    }

    protected $params;
    public function getParams() {
        if (!isset($this->params)) {
            $params = [];
            if (isset($_SERVER['argv'])) {
                $params = $_SERVER['argv'];
                array_shift($params);
            }

            $this->params = $this->resolve($params);
        }

        return $this->params;
    }

    protected function resolve(array $params) {
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