<?php

namespace Akari\utility;

class ApplicationDataMgr {

    protected static $_values = [];

    public static function set($key, $value) {
        self::$_values[$key] = $value;
    }

    public static function get($key, $subKey = NULL, $defaultValue = NULL) {
        if (self::has($key, $subKey)) {
            $r = self::$_values[$key];

            if ($subKey !== NULL) {
                return is_array($r) ? $r[$subKey] : $r->$subKey;
            }

            return $r;
        }

        return $defaultValue;
    }

    public static function update($key, $value, $subKey = NULL) {
        if (!self::has($key))   return FALSE;

        $r = self::$_values[$key];
        if ($subKey === NULL) {
            $r = $value;
        } else {
            if (is_array($r)) $r[$subKey] = $value;
            elseif (is_object($r))  $r->$subKey = $value;
        }

        self::$_values[$key] = $r;

        return TRUE;
    }

    public static function has($key, $subKey = NULL) {
        if (!array_key_exists($key, self::$_values)) {
            return FALSE;
        }

        // 不然取值
        if ($subKey !== NULL) {
            $r = self::$_values[$key];
            if (is_array($r) && array_key_exists($key, $r)) {
                return TRUE;
            } elseif (is_object($r) && property_exists($r, $key)) {
                return TRUE;
            }

            return FALSE;
        }

        return TRUE;
    }

}
