<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:31
 */

namespace Akari\system\util;

class ArrayUtil {

    /**
     * 是否是关联数组
     *
     * @param array $arr
     * @return bool
     */
    public static function isAssoc(array $arr) {
        if(array_keys($arr) !== range(0, count($arr) - 1)) {
            return TRUE;
        }

        return FALSE;
    }

    public static function flatten(array $items, string $columnKey, ?string $indexKey, $allowRepeat = FALSE) {
        $result = [];

        foreach ($items as $item) {
            $itemValue = is_array($item) ? $item[$columnKey] : $item->$columnKey;

            if ($indexKey !== NULL) {
                $itemKey = is_array($item) ? $item[$indexKey] : $item->$indexKey;
                if ($allowRepeat) {
                    $result[$itemKey][] = $itemValue;
                } else {
                    $result[$itemKey] = $itemValue;
                }

                continue;
            }

            $result[] = $itemValue;
        }

        return $result;
    }

    public static function indexMulti(array $items, string $indexKey) {
        $result = [];
        foreach ($items as $item) {
            $key = is_array($item) ? $item[$indexKey] : $item->$indexKey;
            if (!isset($result[$key])) $result[$key] = [];

            $result[$key][] = $item;
        }

        return $result;
    }

    public static function index(array $items, string $indexKey) {
        $result = [];
        foreach ($items as $item) {
            $key = is_array($item) ? $item[$indexKey] : $item->$indexKey;
            $result[$key] = $item;
        }

        return $result;
    }

    public static function reindex(array $items, array $index) {
        $result = [];
        foreach ($index as $key) {
            $result[] = $items[$key];
        }

        return $result;
    }

    public static function exists($items, $key) {
        if ($items instanceof \ArrayAccess) {
            return $items->offsetExists($key);
        }

        return array_key_exists($key, $items);
    }

    public static function except($array, array $keys) {
        static::forget($array, $keys);

        return $array;
    }

    public static function first(array $array) {
        if (!count($array)) return NULL;
        reset($array);

        return $array[ key($array) ];
    }

    public static function last(array $array) {
        if (!count($array)) return NULL;
        end($array);

        return $array[ key($array) ];
    }

    public static function dot($array, $prepend = '') {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    protected static function forget(&$array, $keys) {
        $original = &$array;
        $keys = (array) $keys;
        if (count($keys) === 0) {
            return;
        }
        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);
                continue;
            }
            $parts = explode('.', $key);
            // clean up before each pass
            $array = &$original;
            while (count($parts) > 1) {
                $part = array_shift($parts);
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }
            unset($array[array_shift($parts)]);
        }
    }

    public static function get($array, $key, $default = NULL) {
        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (strpos($key, '.') === FALSE) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if ((is_array($array) || $array instanceof \ArrayAccess) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

}
