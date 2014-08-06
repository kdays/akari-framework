<?php
namespace Akari\system\data;

use Akari\utility\BenchmarkHelper;
use \Exception;
use \Memcached;

!defined("AKARI_VERSION") && exit;

Class MemcachedAdapter extends BaseCacheAdapter{
	public function __construct($confId = 'deafult'){
		if(!class_exists("memcached")){
			throw new Exception("[Akari.Data.MemcachedAdapter] server not found memcached");
		}

		$this->handler = new Memcached();
		$options = $this->getOptions("memcached", $confId, [
			"host" => "127.0.0.1",
			"port" => 11211
		]);
		$this->options = $options;

		$this->resetServerList();
		
		$this->addServer($options['host'], $options['port']);
		if(isset($options['username'])){
			$this->handler->setSaslAuthData($options['username'], $options['password']);
		}

	}

	public function get($name) {
        $result = $this->handler->get($this->options['prefix'].$name);
        if (!$result) {
            BenchmarkHelper::setCacheHit('miss');
            return $defaultValue;
        }

        BenchmarkHelper::setCacheHit('hit');
        return $result;
	}

	/**
	 * 写入缓存
	 *
	 * @access public
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
			return true;
		}

		return false;
	}

    public function remove($key) {
        return $this->handler->remove($this->options['prefix'].$key);
    }
}