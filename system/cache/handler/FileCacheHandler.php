<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/8
 * Time: 下午1:25.
 */

namespace Akari\system\cache\handler;

use Akari\Context;
use Akari\system\cache\CacheBenchmark;
use Akari\system\event\Listener;
use Akari\utility\FileHelper;

class FileCacheHandler implements ICacheHandler
{
    protected $isInTransaction = false;
    protected $transaction = [];

    protected $fileIndex = [];
    protected $indexPath;
    protected $baseDir;

    public function __construct(array $options)
    {
        $indexPath = array_key_exists('indexPath', $options) ? $options['indexPath'] : 'index.json';
        $baseDir = array_key_exists('baseDir', $options) ? $options['baseDir'] : '/runtime/cache/';

        $baseDir = Context::$appBasePath.$baseDir;
        $indexPath = $baseDir.$indexPath;

        $this->baseDir = $baseDir;
        $this->indexPath = $indexPath;

        // 读取fileIndex
        if (!file_exists($indexPath)) {
            FileHelper::write($indexPath, json_encode([]));
        }

        $this->fileIndex = json_decode(FileHelper::read($indexPath), true);
        $this->removeExpired();
    }

    private function removeExpired()
    {
        foreach ($this->fileIndex as $key => $value) {
            if ($value['expire'] > 0 && $value['expire'] < TIMESTAMP) {
                $this->_remove($key, false);
            }
        }

        $this->_updateIndex();
    }

    private function _updateIndex()
    {
        FileHelper::write($this->indexPath, json_encode($this->fileIndex));
    }

    private function _getFileName($key)
    {
        $hash = md5(uniqid());

        return $key.'_'.substr($hash, 6, 11);
    }

    private function _getKey($key)
    {
        return $key;
    }

    private function _set($key, $value, $timeout = null, $doUpdateIndex = true)
    {
        Listener::fire(CacheBenchmark::ACTION_CREATE, ['key' => $key]);
        $savedKey = $this->_getKey($key);

        if (isset($this->fileIndex[$savedKey])) {
            $data = $this->fileIndex[$savedKey];
            if (file_exists($kPath = $this->baseDir.$data['f'])) {
                unlink($kPath);
            }
        }

        $index = $this->_getFileName($savedKey);
        $this->fileIndex[$savedKey] = [
            'f'      => $index,
            'expire' => $timeout > 0 ? (TIMESTAMP + $timeout) : $timeout,
        ];

        FileHelper::write($this->baseDir.$index, serialize($value));
        if ($doUpdateIndex) {
            $this->_updateIndex();
        }
    }

    private function _remove($key, $doUpdateIndex = true)
    {
        Listener::fire(CacheBenchmark::ACTION_REMOVE, ['key' => $key]);
        $savedKey = $this->_getKey($key);
        if (!isset($this->fileIndex[$savedKey])) {
            return false;
        }

        $data = $this->fileIndex[$savedKey];
        $path = $this->baseDir.$data['f'];

        if (file_exists($path)) {
            unlink($path);
        }

        unset($this->fileIndex[$savedKey]);
        if ($doUpdateIndex) {
            $this->_updateIndex();
        }

        return true;
    }

    /**
     * 设置缓存.
     *
     * @param string   $key     键名
     * @param mixed    $value   值
     * @param null|int $timeout 超时时间
     *
     * @return bool
     */
    public function set($key, $value, $timeout = null)
    {
        if ($this->isInTransaction) {
            $this->transaction[] = ['set', $key, $value, $timeout];
        } else {
            $this->_set($key, $value, $timeout, true);
        }

        return true;
    }

    /**
     * 获得缓存设置的键.
     *
     * @param string     $key
     * @param null|mixed $defaultValue
     *
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        if (!isset($this->fileIndex[$key])) {
            CacheBenchmark::log(CacheBenchmark::MISS);

            return $defaultValue;
        }

        // 获得值
        $data = $this->fileIndex[$key];
        $cachePath = $this->baseDir.$data['f'];

        if (!file_exists($cachePath)) {
            $this->_remove($key);

            return $defaultValue;
        }

        CacheBenchmark::log(CacheBenchmark::HIT);

        return unserialize(file_get_contents($cachePath));
    }

    /**
     * 删除某个缓存键.
     *
     * @param string $key
     *
     * @return bool
     */
    public function remove($key)
    {
        if ($this->isInTransaction) {
            $this->transaction[] = ['remove', $key];
        } else {
            $this->_remove($key, true);
        }
    }

    /**
     * 检查某个缓存键是否存在.
     *
     * @param $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return isset($this->fileIndex[$this->_getKey($key)]);
    }

    /**
     * 获得所有的缓存键.
     *
     * @return array
     */
    public function all()
    {
        return array_keys($this->fileIndex);
    }

    /**
     * 开启缓存事务，直到commit才会递交.
     *
     * <b>部分缓存采用的是模拟实现，即临时存入数组，请注意内存消耗</b>
     *
     * @return bool
     */
    public function startTransaction()
    {
        if ($this->isInTransaction) {
            return false;
        }

        $this->isInTransaction = true;

        return true;
    }

    /**
     * 是否处于事务状态中.
     *
     * @return bool
     */
    public function inTransaction()
    {
        return (bool) $this->isInTransaction;
    }

    /**
     * 提交一个事务
     *
     * 成功返回True，没有处于事务或没有任何更新提交，返回False
     *
     * @return bool
     */
    public function commit()
    {
        if (!$this->isInTransaction) {
            return false;
        }

        $this->isInTransaction = false;
        if (empty($this->transaction)) {
            return false;
        }

        foreach ($this->transaction as $command) {
            $cmd = '_'.array_shift($command);
            call_user_func_array([$this, $cmd], array_merge($command, [false]));
        }

        $this->_updateIndex();

        return true;
    }

    /**
     * 将目前处于事务状态的内容撤销提交.
     *
     * @return bool
     */
    public function rollback()
    {
        $this->isInTransaction = false;
        $this->transaction = [];
    }

    /**
     * @throws CacheHandlerMethodNotSupport
     */
    public function getHandler()
    {
        throw new CacheHandlerMethodNotSupport(__CLASS__, __METHOD__);
    }

    /**
     * 清空数据库.
     *
     * @return mixed
     */
    public function flush()
    {
        foreach ($this->fileIndex as $key => $value) {
            $this->remove($key);
        }
    }

    /**
     * 原子计数，加.
     *
     * @param string $key
     * @param int    $value
     *
     * @return int
     */
    public function increment($key, $value = 1)
    {
        // TODO: Implement increment() method.
        $value += $this->get($key);

        return $this->set($key, $value);
    }

    /**
     * 原子计数，减.
     *
     * @param string $key
     * @param int    $value
     *
     * @return int
     */
    public function decrement($key, $value = 1)
    {
        // TODO: Implement decrement() method.
        return $this->increment($key, $value * -1);
    }

    /**
     * 是否支持事务
     *
     * @return bool
     */
    public function isSupportTransaction()
    {
        return true;
    }
}
