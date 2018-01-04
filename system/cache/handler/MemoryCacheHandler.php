<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/10/9
 * Time: 上午8:47
 */

namespace Akari\system\cache\handler;

/**
 * WARNING: 这并不是一个缓存类
 * 
 * Class MemoryCacheHandler
 * @package Akari\system\cache\handler
 */
class MemoryCacheHandler implements ICacheHandler{

    protected $data = [];

    public function set($key, $value, $timeout = NULL, $raw = FALSE) {
        $this->data[$key] = $raw ? $value : unserialize($raw);
    }

    public function get($key, $defaultValue = NULL, $raw = FALSE) {
        if ($this->exists($key)) {
            return $raw ? $this->data[$key] : unserialize($this->data[$key]);
        }

        return $defaultValue;
    }

    public function exists($key) {
        return array_key_exists($key, $this->data);
    }

    public function all() {
        return $this->data;
    }

    public function startTransaction() {
        throw new CacheHandlerMethodNotSupport(__CLASS__, __METHOD__);
    }

    public function inTransaction() {
        throw new CacheHandlerMethodNotSupport(__CLASS__, __METHOD__);
    }

    public function commit() {
        throw new CacheHandlerMethodNotSupport(__CLASS__, __METHOD__);
    }

    public function rollback() {
        throw new CacheHandlerMethodNotSupport(__CLASS__, __METHOD__);
    }

    public function getHandler() {
        throw new CacheHandlerMethodNotSupport(__CLASS__, __METHOD__);
    }

    public function remove($key) {
        unset($this->data[$key]);
    }

    public function flush() {
        $this->data = [];
    }

    public function increment($key, $value = 1) {
        $this->data[$key] += $value;

        return $this->data[$key];
    }

    public function decrement($key, $value = 1) {
        return $this->increment($key, $value * -1);
    }

    /**
     * 是否支持事务
     *
     * @return bool
     */
    public function isSupportTransaction() {
        return FALSE;
    }
}
