<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:06
 */

namespace Akari\system\cache;

use Redis;

Class RedisCache extends ICache{

    public function __construct($redisName = 'default'){
        $this->handler = new Redis();
        $options = $this->getOption("redis", $redisName, [
            'port' => 6379,
            'host' => '127.0.0.1',
            'timeout' => 600
        ]);

        if (!empty($options['password'])) {
            $this->handler->auth($options['password']);
        }
        $this->handler->connect($options['host'], $options['port'], $options['timeout']);
    }

    public function set($key, $value) {
        CacheBenchmark::log(CacheBenchmark::ACTION_CREATE);
        return $this->handler->set($key, $value);
    }

    public function get($key, $defaultValue = NULL) {
        $result = $this->handler->get($this->options['prefix'].$key);
        if (!$result) {
            CacheBenchmark::log(CacheBenchmark::MISS);
            return $defaultValue;
        }

        CacheBenchmark::log(CacheBenchmark::HIT);
        return $result;
    }

    /**
     * 删除缓存
     *
     * @param string $key
     */
    public function remove($key) {
        CacheBenchmark::log(CacheBenchmark::ACTION_REMOVE);
        $this->handler->delete($key);
    }

}