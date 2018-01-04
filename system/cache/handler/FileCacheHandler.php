<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/8
 * Time: 下午1:25
 */

namespace Akari\system\cache\handler;

use Akari\system\event\Listener;
use Akari\system\storage\Storage;
use Akari\system\storage\StorageDisk;
use Akari\system\cache\CacheBenchmark;

class FileCacheHandler implements ICacheHandler{

    protected $isInTransaction = FALSE;
    protected $transaction = [];

    protected $fileIndex = [];

    /** @var StorageDisk */
    protected $_storage;
    protected $indexName;

    public function __construct(array $options) {
        $indexName = array_key_exists("indexPath", $options) ? $options['indexPath'] : 'index.json';
        $cacheStorage = array_key_exists('storage', $options) ? $options['storage'] : '.cache';

        $cacheStorage = Storage::disk($cacheStorage);

        $this->_storage = $cacheStorage;
        $this->indexName = $indexName;

        // 读取fileIndex
        if (!$cacheStorage->exists($indexName)) {
            $cacheStorage->put($indexName, json_encode([]));
        }

        $this->fileIndex = json_decode($cacheStorage->get($indexName), TRUE);
        $this->removeExpired();
    }

    private function removeExpired() {

        foreach ($this->fileIndex as $key => $value) {
            if ($value['expire'] > 0 && $value['expire'] < TIMESTAMP) {
                $this->_remove($key, FALSE);
            }
        }

        $this->_updateIndex();
    }

    private function _updateIndex() {
        $this->_storage->put($this->indexName, json_encode($this->fileIndex));
    }

    private function _getFileName($key) {
        $hash = md5(uniqid());

        return $key . "_" . substr($hash, 6, 11);
    }

    private function _getKey($key) {
        return $key;
    }

    private function _set($key, $value, $timeout = NULL, $doUpdateIndex = TRUE, $rawValue = FALSE) {
        Listener::fire(CacheBenchmark::ACTION_CREATE, ['key' => $key]);
        $savedKey = $this->_getKey($key);
        $storage = $this->_storage;

        if (isset($this->fileIndex[$savedKey])) {
            $data = $this->fileIndex[$savedKey];
            if ($storage->exists($data['f'])) {
                $storage->delete($data['f']);
            }
        }

        $index = $this->_getFileName($savedKey);
        $this->fileIndex[$savedKey] = [
            'f' => $index,
            'expire' => $timeout > 0 ? (TIMESTAMP + $timeout) : $timeout
        ];

        $storage->put($index, $rawValue ? $value : serialize($value));
        if ($doUpdateIndex) $this->_updateIndex();
    }

    private function _remove($key, $doUpdateIndex = TRUE) {
        Listener::fire(CacheBenchmark::ACTION_REMOVE, ['key' => $key]);
        $savedKey = $this->_getKey($key);
        $storage = $this->_storage;

        if (!isset($this->fileIndex[$savedKey])) {
            return FALSE;
        }

        $data = $this->fileIndex[$savedKey];
        if ($storage->exists($data['f'])) {
            $storage->delete($data['f']);
        }

        unset($this->fileIndex[$savedKey]);
        if ($doUpdateIndex) $this->_updateIndex();

        return TRUE;
    }

    /**
     * 设置缓存
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @param null|int $timeout 超时时间
     * @param bool $raw
     * @return boolean
     */
    public function set($key, $value, $timeout = NULL, $raw = FALSE) {
        if ($this->isInTransaction) {
            $this->transaction[] = ['set', $key, $value, $timeout, $raw];
        } else {
            $this->_set($key, $value, $timeout, TRUE);
        }

        return TRUE;
    }

    /**
     * 获得缓存设置的键
     *
     * @param string $key
     * @param null|mixed $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = NULL, $raw = FALSE) {
        if (!isset($this->fileIndex[$key])) {
            CacheBenchmark::log(CacheBenchmark::MISS);

            return $defaultValue;
        }

        $storage = $this->_storage;
        // 获得值
        $data = $this->fileIndex[$key];
        $cachePath = $data['f'];

        if (!$storage->exists($cachePath)) {
            $this->_remove($key);

            return $defaultValue;
        }

        CacheBenchmark::log(CacheBenchmark::HIT);

        $value = $storage->get($cachePath);
        return $raw ? $value : unserialize($value);
    }

    /**
     * 删除某个缓存键
     *
     * @param string $key
     * @return boolean
     */
    public function remove($key) {
        if ($this->isInTransaction) {
            $this->transaction[] = ['remove', $key];
        } else {
            $this->_remove($key, TRUE);
        }
    }

    /**
     * 检查某个缓存键是否存在
     *
     * @param $key
     * @return boolean
     */
    public function exists($key) {
        return isset($this->fileIndex[$this->_getKey($key)]);
    }

    /**
     * 获得所有的缓存键
     *
     * @return array
     */
    public function all() {
        return array_keys($this->fileIndex);
    }

    /**
     * 开启缓存事务，直到commit才会递交
     *
     * <b>部分缓存采用的是模拟实现，即临时存入数组，请注意内存消耗</b>
     *
     * @return boolean
     */
    public function startTransaction() {
        if ($this->isInTransaction) {
            return FALSE;
        }

        $this->isInTransaction = TRUE;

        return TRUE;
    }

    /**
     * 是否处于事务状态中
     *
     * @return boolean
     */
    public function inTransaction() {
        return !!$this->isInTransaction;
    }

    /**
     * 提交一个事务
     *
     * 成功返回True，没有处于事务或没有任何更新提交，返回False
     *
     * @return boolean
     */
    public function commit() {
        if (!$this->isInTransaction) {
            return FALSE;
        }

        $this->isInTransaction = FALSE;
        if (empty($this->transaction)) {
            return FALSE;
        }

        foreach ($this->transaction as $command) {
            $cmd = "_" . array_shift($command);
            call_user_func_array([$this, $cmd], array_merge($command, [FALSE]));
        }

        $this->_updateIndex();

        return TRUE;
    }

    /**
     * 将目前处于事务状态的内容撤销提交
     *
     * @return boolean
     */
    public function rollback() {
        $this->isInTransaction = FALSE;
        $this->transaction = [];
    }

    /**
     * @throws CacheHandlerMethodNotSupport
     */
    public function getHandler() {
        throw new CacheHandlerMethodNotSupport(__CLASS__, __METHOD__);
    }


    /**
     * 清空数据库
     * @return mixed
     */
    public function flush() {
        foreach ($this->fileIndex as $key => $value) {
            $this->remove($key);
        }
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
        $value += $this->get($key);

        return $this->set($key, $value);
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
        return $this->increment($key, $value * -1);
    }

    /**
     * 是否支持事务
     *
     * @return bool
     */
    public function isSupportTransaction() {
        return TRUE;
    }
}
