<?php
namespace Akari\system\data;

use Akari\utility\BenchmarkHelper;
use \Redis;

!defined("AKARI_VERSION") && exit;

Class RedisAdapter extends BaseCacheAdapter {
    /**
     * @param string $redisName Redis配置名
     * @throws \Exception
     */
    public function __construct($redisName = 'default'){
        if (!class_exists("redis")) {
            throw new \Exception("[Akari.data.RedisAdapter] server not found redis");
        }

        $this->handler = new Redis();
        $options = $this->getOptions("redis", $redisName, [
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
        return $this->handler->set($key, $value);
    }

    public function get($key, $defaultValue = NULL) {
        $result = $this->handler->get($this->options['prefix'].$key);
        if (!$result) {
            $this->benchmark(BenchmarkHelper::FLAG_MISS);
            return $defaultValue;
        }

        $this->benchmark(BenchmarkHelper::FLAG_HIT);
        return $result;
    }

    /**
     * 删除缓存
     *
     * @param string $key
     */
    public function remove($key) {
        $this->handler->delete($key);
    }
}