<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/10/9
 * Time: 上午9:00
 */

namespace Akari\system\cache\handler;


class NullCacheHandler implements ICacheHandler{

    /**
     * 设置缓存
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @param null|int $timeout 超时时间
     * @return boolean
     */
    public function set($key, $value, $timeout = NULL) {
        // TODO: Implement set() method.
        return TRUE;
    }

    /**
     * 获得缓存设置的键
     *
     * @param string $key
     * @param null|mixed $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = NULL) {
        // TODO: Implement get() method.
        return $defaultValue;
    }

    /**
     * 删除某个缓存键
     *
     * @param string $key
     * @return boolean
     */
    public function remove($key) {
        // TODO: Implement remove() method.
        return TRUE;
    }

    /**
     * 检查某个缓存键是否存在
     *
     * @param $key
     * @return boolean
     */
    public function exists($key) {
        // TODO: Implement exists() method.
        return FALSE;
    }

    /**
     * 获得所有的缓存键
     *
     * @return array
     */
    public function all() {
        // TODO: Implement all() method.
        return [];
    }

    /**
     * 清空数据库
     * @return mixed
     */
    public function flush() {
        // TODO: Implement flush() method.
    }

    /**
     * 原子计数，加
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function increment($key, $value = 1) {
        // TODO: Implement increment() method.
        return $value;
    }

    /**
     * 原子计数，减
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function decrement($key, $value = 1) {
        // TODO: Implement decrement() method.
        return $value;
    }

    /**
     * 开启缓存事务，直到commit才会递交
     *
     * 如果你一时要对大量缓存进行变更时，可以减少对于缓存索引的压力
     *
     * 请注意 和数据库不同，文件缓存的模拟事务并不会在commit前执行
     *
     * @return boolean
     */
    public function startTransaction() {
        // TODO: Implement startTransaction() method.
        throw new CacheHandlerMethodNotSupport();
    }

    /**
     * 是否处于事务状态中
     *
     * @return boolean
     */
    public function inTransaction() {
        // TODO: Implement inTransaction() method.
        return FALSE;
    }

    public function commit() {
        // TODO: Implement commit() method.
        throw new CacheHandlerMethodNotSupport();
    }

    public function rollback() {
        // TODO: Implement rollback() method.
        throw new CacheHandlerMethodNotSupport();
    }

    /**
     * 获得原生缓存对象，如Redis返回Redis对象
     *
     * 如文件没有的则会抛出错误
     *
     * @return mixed
     */
    public function getHandler() {
        // TODO: Implement getHandler() method.
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