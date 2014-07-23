<?php
namespace Akari\system\data;

!defined("AKARI_PATH") && exit;

Class MemcacheAdapter extends BaseCacheAdapter{
	public function __construct(){
		if(!class_exists("memcache")){
			throw new Exception("[Akari.Data.MemcacheAdapter] server not found memcache");
		}

		$options = $this->getOptions("memcache", Array(
			"host"		=> "127.0.0.1",
			"port"		=> 11211,
			"timeout"	=> 30,
			"prefix"	=> ''
		));
		$this->options = $options;

		$this->handler = new Memcache();
		if(!$this->handler->connect($options['host'], $options['port'], $options['timeout'])){
			throw new Exception("[Akari.Data.MemcacheAdapter] Connect $options[host] Error");
		}
	}

	public function remove($name){
		return $this->handler->delete($this->options['prefix'].$name);
	}
	
	public function get($name, $defaultValue = NULL) {
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
		if($this->handler->set($name, $value, 0, $expire)) {
			return true;
		}

		return false;
	}
}