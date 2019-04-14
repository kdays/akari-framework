<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:13
 */

namespace Akari\system\ioc;

use Akari\exception\AkariException;

class DI {

    protected $services = [];
    protected $instances = [];

    protected static $defaultInstance = NULL;
    public static function getDefault() {
        if (!isset(self::$defaultInstance)) self::$defaultInstance = new self();

        return self::$defaultInstance;
    }

    public function set(string $name, $call) {
        $this->services[$name] = $call;
    }

    public function get(string $name) {
        if (!$this->has($name)) {
            throw new AkariException("module " . $name . " not exists");
        }

        return $this->getFn( $this->services[$name] );
    }

    public function has(string $name) {
        return array_key_exists($name, $this->services);
    }

    public function hasShared(string $name) {
        return array_key_exists($name, $this->instances);
    }

    public function setShared(string $name, $call) {
        $this->instances[$name] = $this->getFn($call);
    }

    public function getShared(string $name) {
        if (!$this->hasShared($name)) {
            throw new AkariException("instance " . $name . " no set up");
        }

        return $this->instances[$name];
    }

    protected function getFn($call) {
        if (is_callable($call)) {
            return call_user_func_array($call, [$this]);
        } elseif (class_exists($call)) {
            return new $call();
        }

        return NULL;
    }

}
