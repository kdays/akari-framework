<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-27
 * Time: 15:59
 */

namespace Akari\system\util;

use Akari\system\ioc\Injectable;

class I18n extends Injectable {

    protected $data = [];

    public function register(array $lang, string $prefix = '') {
        foreach ($lang as $key => $text) {
            $this->data[ $prefix . $key ] = $text;
        }
    }

    public function has(string $key, string $prefix = '') {
        return array_key_exists($prefix . $key, $this->data);
    }

    public function get(string $key, array $L = [], string $prefix = '') {
        if (!$this->has($key, $prefix)) return $key;

        $lang = $this->data[ $prefix . $key ];
        foreach ($L as $k => $v) {
            $lang = str_replace("%" . $k . "%", $v, $lang);
        }

        // 处理![语言句子] 或被替换成L(语言句子)
        $that = $this;
        $lang = preg_replace_callback('/\!\[(\S+)\]/i', function ($matches) use ($that) {
            if (isset($matches[1])) {
                return $that->get($matches[1]);
            }

            return $matches[0];
        }, $lang);

        return $lang;
    }

    public function batchFetch(array $keys, string $prefix = '') {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, [], $prefix);
        }

        return $result;
    }

}
