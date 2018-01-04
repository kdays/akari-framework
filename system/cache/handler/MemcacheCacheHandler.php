<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/25
 * Time: 上午8:24
 */

namespace Akari\system\cache\handler;

use Akari\system\event\Listener;
use Akari\system\cache\Exception;
use Akari\system\cache\CacheBenchmark;

class MemcacheCacheHandler implements ICacheHandler{

    /** @var \Memcache $handler */
    protected $handler;

    protected $host;
    protected $port;

    public function __construct(array $opts) {
        $memcache = new \Memcache();

        $host = array_key_exists("host", $opts) ? $opts['host'] : '127.0.0.1';
        $port = array_key_exists("port", $opts) ? $opts['port'] : 11211;
        $timeout = array_key_exists("timeout", $opts) ? $opts['timeout'] : 5;

        $this->host = $host;
        $this->port = $port;

        if (!$memcache->connect($host, $port, $timeout)) {
            throw new Exception("[MemcacheCacheHandler] Connect Failed: " . $host . ":" . $port);
        }

        $this->handler = $memcache;
    }

    public function get($name, $defaultValue = NULL, $raw = FALSE) {
        $value = $this->handler->get($name);
        if (!$value) {
            return $defaultValue;
        }

        return $raw ? $value : unserialize($value);
    }

    public function set($key, $value, $timeout = NULL, $raw = FALSE) {
        Listener::fire(CacheBenchmark::ACTION_CREATE, ['key' => $key]);
        $value = $raw ? $value : serialize($value);

        return $this->handler->set($key, $value, 0, $timeout);
    }

    /**
     * 删除某个缓存键
     *
     * @param string $key
     * @return boolean
     */
    public function remove($key) {
        return $this->handler->delete($key);
    }

    /**
     * 检查某个缓存键是否存在
     *
     * @param $key
     * @return boolean
     */
    public function exists($key) {
        return !!$this->handler->get($key);
    }

    /**
     * 获得所有的缓存键
     *
     * @return array
     */
    public function all() {
        $memcache = $this->handler;

        $list = array();
        $allSlabs = $memcache->getExtendedStats('slabs');
        $items = $memcache->getExtendedStats('items');
        foreach($allSlabs as $server => $slabs) {
            foreach($slabs as $slabId => $slabMeta) {
                $cdump = $memcache->getExtendedStats('cachedump', (int) $slabId);
                foreach($cdump as $keys => $arrVal) {
                    foreach($arrVal as $k => $v){
                        $list[] = $k;
                    }
                }
            }
        }

        return $list;
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

    /**
     * 获得原生缓存对象，如Redis返回Redis对象
     *
     * 如文件没有的则会抛出错误
     *
     * @return mixed
     */
    public function getHandler() {
        return $this->handler;
    }

    public function flush() {
        return $this->handler->flush();
    }

    public function increment($key, $value = 1) {
        return $this->handler->increment($key, $value);
    }

    public function decrement($key, $value = 1) {
        return $this->handler->decrement($key, $value);
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
