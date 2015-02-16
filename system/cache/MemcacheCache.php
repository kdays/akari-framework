<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:06
 */

namespace Akari\system\cache;

use Memcached;

Class MemcacheCache extends ICache {

    public function __construct($confId = 'default') {
        $this->handler = new Memcached();

        $options = $this->getOption("memcache", $confId, [
            "host" => "127.0.0.1",
            "port" => 11211
        ]);
        $this->options = $options;

        // Memcached的高级特性关闭掉
        $this->handler->resetServerList();
        $this->handler->addServer($options['host'], $options['port']);

        $key = $options['host']. ":". $options['port'];
        if ($this->handler->getVersion()[$key] == '255.255.255') {
            throw new \MemcachedException("Memcache Server [ $key ] can not connect");
        }

        if(isset($options['username'])){
            $this->handler->setSaslAuthData($options['username'], $options['password']);
        }
    }

    public function get($name, $defaultValue = NULL) {
        $result = $this->handler->get($this->options['prefix'].$name);
        if (!$result) {
            CacheBenchmark::log(CacheBenchmark::MISS);
            return $defaultValue;
        }

        CacheBenchmark::log(CacheBenchmark::HIT);
        return $result;
    }

    /**
     * 写入缓存
     *
     * @param string $name 缓存变量名
     * @param mixed $value  存储数据
     * @param integer $expire  有效时间（秒）
     * @return boolean
     */
    public function set($name, $value, $expire = null) {
        if(is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $name = $this->options['prefix'].$name;
        if($this->handler->set($name, $value, $expire)) {
            CacheBenchmark::log(CacheBenchmark::ACTION_CREATE);
            return true;
        }

        return false;
    }

    public function remove($key) {
        CacheBenchmark::log(CacheBenchmark::ACTION_REMOVE);
        return $this->handler->delete($this->options['prefix'].$key);
    }

}