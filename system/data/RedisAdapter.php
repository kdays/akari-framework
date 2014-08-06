<?php
namespace Akari\system\data;

use \Redis;

!defined("AKARI_VERSION") && exit;

Class RedisAdapter extends BaseCacheAdapter {
    public function __construct($redisName){
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

    public function get($key) {
        return $this->handler->get($key);
    }

    /**
     * @param $key
     */
    public function remove($key) {
        $this->handler->delete($key);
    }
}