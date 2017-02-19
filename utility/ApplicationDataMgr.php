<?php

namespace Akari\utility;

class ApplicationDataMgr
{
    protected static $_values = [];

    public static function set($key, $value)
    {
        self::$_values[$key] = $value;
    }

    public static function get($key, $subKey = null, $defaultValue = null)
    {
        if (self::has($key, $subKey)) {
            $r = self::$_values[$key];

            if ($subKey !== null) {
                return is_array($r) ? $r[$subKey] : $r->$subKey;
            }

            return $r;
        }

        return $defaultValue;
    }

    public static function update($key, $value, $subKey = null)
    {
        if (!self::has($key)) {
            return false;
        }

        $r = self::$_values[$key];
        if ($subKey === null) {
            $r = $value;
        } else {
            if (is_array($r)) {
                $r[$subKey] = $value;
            } elseif (is_object($r)) {
                $r->$subKey = $value;
            }
        }

        self::$_values[$key] = $r;

        return true;
    }

    public static function has($key, $subKey = null)
    {
        if (!array_key_exists($key, self::$_values)) {
            return false;
        }

        // 不然取值
        if ($subKey !== null) {
            $r = self::$_values[$key];
            if (is_array($r) && array_key_exists($key, $r)) {
                return true;
            } elseif (is_object($r) && property_exists($r, $key)) {
                return true;
            }

            return false;
        }

        return true;
    }
}
