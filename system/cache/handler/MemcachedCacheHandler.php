<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/8
 * Time: 下午2:35
 */

namespace Akari\system\cache\handler;

use Akari\system\cache\CacheBenchmark;

class MemcachedCacheHandler {

    /** @var \Memcached */
    protected $handler;

    protected $host;
    protected $port;
    protected $password;
    protected $username;

    public function __construct(array $opts) {
        $memcached = new \Memcached();

        $host = array_key_exists("host", $opts) ? $opts['host'] : '127.0.0.1';
        $port = array_key_exists("port", $opts) ? $opts['port'] : 11211;

        $memcached->resetServerList();
        $memcached->addServer($host, $port);

        /*$key = $host. ":". $port;
        if ($this->handler->getVersion()[$key] == '255.255.255') {
            throw new \MemcachedException("memcached [ $key ] connection error");
        }*/ //对于阿里云的OCS而言 永远都是ERROR

        $this->host = $host;
        $this->port = $port;

        $username = array_key_exists("username", $opts) ? $opts['username'] : false;
        $password = array_key_exists("password", $opts) ? $opts['password'] : false;

        $this->username = $username;
        $this->password = $password;
        if ($password) {
            $memcached->setSaslAuthData($username, $password);
        }
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
        $value = serialize($value);
        
        $this->handler->set($key, $value, $timeout);
        if ($this->handler->getResultCode() == \Memcached::RES_SUCCESS) {
            return True;
        } else {
            throw new \MemcachedException($this->handler->getResultMessage(), $this->handler->getResultCode());
        }
    }

    /**
     * 获得缓存设置的键
     *
     * @param string $key
     * @param null|mixed $defaultValue
     * @return mixed
     */
    public function get($key, $defaultValue = NULL) {
        $value = $this->handler->get($key);

        $retCode = $this->handler->getResultCode();
        switch ($retCode) {
            case \Memcached::RES_SUCCESS:
                CacheBenchmark::log(CacheBenchmark::HIT);
                return unserialize($value);

            case \Memcached::RES_NOTFOUND:
            case \Memcached::RES_TIMEOUT:
                CacheBenchmark::log(CacheBenchmark::MISS);
                return $defaultValue;

            default:
                throw new \MemcachedException($this->handler->getResultMessage(), $this->handler->getResultCode());
        }
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
        return $this->handler->getAllKeys();
    }

    public function startTransaction() {
        throw new CacheHandlerMethodNotSupport();
    }

    public function inTransaction() {
        throw new CacheHandlerMethodNotSupport();
    }

    public function commit() {
        throw new CacheHandlerMethodNotSupport();
    }

    public function rollback() {
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
        return $this->handler;
    }

}