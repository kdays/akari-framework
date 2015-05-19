<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/5/19
 * Time: 下午10:56
 */
namespace Akari\system\ioc;

use Akari\NotFoundClass;

class DI {

    protected static $defaultInstance = NULL;

    public static function getDefault() {
        if (!isset(self::$defaultInstance)) {
            self::$defaultInstance = new self();
        }

        return self::$defaultInstance;
    }

    protected $services = [];
    protected $instance = [];

    public function set($serviceName, $cls) {
        $this->services[$serviceName] = $cls;
    }

    public function get($serviceName) {
        if (array_key_exists($serviceName, $this->services)) {
            if (is_string($this->services[$serviceName])) {
                $clsObj = new $this->services[$serviceName]();
            } else {
                $clsObj = $this->services[$serviceName]();
            }

            return $clsObj;
        }
    }

    public function setShared($serviceName, $cls) {
        if (is_string($cls)) {
            $clsObj = new $cls();
        } else {
            $clsObj = $cls();
        }

        $this->instance[$serviceName] = $clsObj;
    }

    public function getShared($serviceName) {
        return $this->instance[$serviceName];
    }

}