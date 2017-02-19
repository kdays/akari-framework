<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/5/19
 * Time: 下午10:56.
 */

namespace Akari\system\ioc;

use core\system\ioc\DINotRegistered;

class DI
{
    protected static $defaultInstance = null;

    public static function getDefault()
    {
        if (!isset(self::$defaultInstance)) {
            self::$defaultInstance = new self();
        }

        return self::$defaultInstance;
    }

    protected $services = [];
    protected $instance = [];

    public function set($serviceName, $cls)
    {
        $this->services[$serviceName] = $cls;
    }

    public function get($serviceName)
    {
        if ($this->has($serviceName)) {
            if (is_string($this->services[$serviceName])) {
                $clsObj = new $this->services[$serviceName]();
            } else {
                $clsObj = $this->services[$serviceName]();
            }

            return $clsObj;
        }

        throw new DINotRegistered('DI:'.$serviceName);
    }

    public function has($serviceName)
    {
        return (bool) array_key_exists($serviceName, $this->services);
    }

    public function setShared($serviceName, $cls)
    {
        if (is_string($cls)) {
            $clsObj = new $cls();
        } else {
            $clsObj = $cls();
        }

        $this->instance[$serviceName] = $clsObj;
    }

    public function getShared($serviceName)
    {
        if (!$this->hasShared($serviceName)) {
            throw new DINotRegistered('Shared:DI:'.$serviceName);
        }

        return $this->instance[$serviceName];
    }

    public function hasShared($serviceName)
    {
        return (bool) isset($this->instance[$serviceName]);
    }
}
