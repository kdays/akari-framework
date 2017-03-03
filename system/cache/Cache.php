<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/8
 * Time: 下午8:17
 */

namespace Akari\system\cache;

use Akari\Context;

class Cache {

    protected static $s;

    /**
     * @param $configKey
     * @return Cache
     */
    public static function getInstance($configKey = 'default') {
        if (!isset(self::$s[$configKey])) {
            $conf = Context::$appConfig->cache[$configKey];
            self::$s[$configKey] = new self($conf['handler'], $conf);
        }

        return self::$s[$configKey];
    }

    /** @var handler\ICacheHandler  */
    protected $cacheHandler;

    public function __construct($handlerName, $options) {
        $handler = new $handlerName($options);
        $this->cacheHandler = $handler;
    }

    /**
     * 设置缓存
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @param null|int $timeout 超时时间
     * @return boolean
     */
    public function set($key, $value, $timeout = NULL) {
        return $this->cacheHandler->set($key, $value, $timeout);
    }

    /**
     * 获得缓存设置的键
     *
     * @param string $key
     * @param null|mixed $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = NULL) {
        return $this->cacheHandler->get($key, $defaultValue);
    }

    /**
     * 删除某个缓存键
     *
     * @param string $key
     * @return boolean
     */
    public function remove($key) {
        return $this->cacheHandler->remove($key);
    }

    /**
     * 检查某个缓存键是否存在
     *
     * @param $key
     * @return boolean
     */
    public function exists($key) {
        return $this->cacheHandler->exists($key);
    }

    /**
     * 获得所有的缓存键
     *
     * @return array
     */
    public function all() {
        return $this->cacheHandler->all();
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
        return $this->cacheHandler->startTransaction();
    }

    /**
     * 是否处于事务状态中
     *
     * @return boolean
     */
    public function inTransaction() {
        return $this->cacheHandler->inTransaction();
    }

    /**
     * 提交一个事务
     *
     * 成功返回True，没有处于事务或没有任何更新提交，返回False
     *
     * @return boolean
     */
    public function commit() {
        return $this->cacheHandler->commit();
    }

    /**
     * 将目前处于事务状态的内容撤销提交
     *
     * @return boolean
     */
    public function rollback() {
        return $this->cacheHandler->rollback();
    }

    /**
     * 获得原生缓存对象，如Redis返回Redis对象
     *
     * 如文件没有的则会抛出错误
     *
     * @return mixed
     */
    public function getHandler() {
        return $this->cacheHandler->getHandler();
    }

    public function increment($key, $value = 1) {
        return $this->cacheHandler->increment($key, $value);
    }

    public function decrement($key, $value = 1) {
        return $this->cacheHandler->decrement($key, $value);
    }


}
