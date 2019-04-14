<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:31
 */

namespace Akari\system\util;

class ArrayUtil {

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

    public static function except($items, array $keys) {
        if (count($keys) == 0) return $items;

        foreach ($keys as $key) {
            if (self::exists($items, $key)) {
                unset($items[$key]);
            }
        }

        return $items;
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

}
