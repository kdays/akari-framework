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

    public static function accessible($value) {
        return is_array($value) || $value instanceof \ArrayAccess;
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

    public static function index( $items, string $indexKey) {
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

    public static function crossJoin(...$arrays) {
        $results = [[]];

        foreach ($arrays as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;

                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    public static function generateArrayFromPath($path, $value) {
        $pathParts = is_array($path) ? $path : explode('.', $path);
        $result = [];
        $lastKey = array_pop($pathParts);

        $current = &$result;
        foreach ($pathParts as $part) {
            if ($part === '*') {
                $current = [];
            } else {
                $current[$part] = [];
                $current = &$current[$part];
            }
        }

        if ($lastKey === '*') {
            $current = $value;
        } else {
            foreach ($value as $index => $val) {
                $current[$index][$lastKey] = $val;
            }
        }

        return $result;
    }

    public static function deepMerge(...$arrays) {
        $result = [];

        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                    // 如果当前值和结果中的值都是数组，则递归合并
                    $result[$key] = self::deepMerge($result[$key], $value);
                } else {
                    // 否则直接覆盖或添加
                    $result[$key] = $value;
                }
            }
        }

        return $result;
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

    public static function collapse($array) {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            } elseif (! is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return array_merge([], ...$results);
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

    public static function set(&$array, $key, $value) {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    public static function get($array, $key, $default = null) {
        if (! static::accessible($array)) {
            return $default;
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        if (! str_contains($key, '.')) {
            return $array[$key] ?? $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    public static function dot_set(&$target, $key, $value, $overwrite = true) {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*') {
            if (! ArrayUtil::accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    ArrayUtil::dot_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (ArrayUtil::accessible($target)) {
            if ($segments) {
                if (! ArrayUtil::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                ArrayUtil::dot_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! ArrayUtil::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                ArrayUtil::dot_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                ArrayUtil::dot_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }

    public static function dot_forget(&$array, $key) {
        $segments = is_array($key) ? $key : explode('.', $key);

        if (($segment = array_shift($segments)) === '*' && ArrayUtil::accessible($array)) {
            if ($segments) {
                foreach ($array as &$inner) {
                    self::dot_forget($inner, $segments);
                }
            }
        } elseif (ArrayUtil::accessible($array)) {
            if ($segments && ArrayUtil::exists($array, $segment)) {
                data_forget($array[$segment], $segments);
            } else {
                ArrayUtil::forget($array, $segment);
            }
        } elseif (is_object($array)) {
            if ($segments && isset($array->{$segment})) {
                self::dot_forget($array->{$segment}, $segments);
            } elseif (isset($array->{$segment})) {
                unset($array->{$segment});
            }
        }

        return $array;
    }

    public static function dot_get($array, $key, $default = NULL) {
        if (is_null($key)) {
            return $array;
        }

        $key = is_array($key) ? $key : explode('.', $key);
        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $array;
            }

            if ($segment === '*') {
                if ($array instanceof Collection) {
                    $array = $array->all();
                } elseif (! is_iterable($array)) {
                    return $default;
                }

                $result = [];

                foreach ($array as $item) {
                    $result[] = ArrayUtil::dot_get($item, $key);
                }

                return in_array('*', $key) ? ArrayUtil::collapse($result) : $result;
            }

            $segment = match ($segment) {
                '\*' => '*',
                '\{first}' => '{first}',
                '{first}' => array_key_first(is_array($array) ? $array : Collection::make($array)->all()),
                '\{last}' => '{last}',
                '{last}' => array_key_last(is_array($array) ? $array : Collection::make($array)->all()),
                default => $segment,
            };

            if (ArrayUtil::accessible($array) && ArrayUtil::exists($array, $segment)) {
                $array = $array[$segment];
            } elseif (is_object($array) && isset($array->{$segment})) {
                $array = $array->{$segment};
            } else {
                return $default;
            }
        }

        return $array;
    }

}
