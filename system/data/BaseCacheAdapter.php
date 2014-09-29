<?php
namespace Akari\system\data;

use Akari\Context;
use Akari\utility\BenchmarkHelper;

!defined("AKARI_PATH") && exit;

abstract Class BaseCacheAdapter{

	protected $handler;
	protected $options = array();

	public function __get($name) {
		return $this->get($name);
	}

	public function __set($name,$value) {
		return $this->set($name,$value);
	}
	
	public function __unset($name){
		return $this->remove($name);
	}

    abstract public function remove($key);
    abstract public function get($key);
    abstract public function set($key, $value);

    /**
     * @param string $flag BenchmarkHelper::FLAG_*
     */
    public function benchmark($flag) {
        BenchmarkHelper::setCacheHit($flag);
    }

    /**
     * 获得缓存配置
     *
     * @param string $key 项目
     * @param string $itemKey 项目配置的id
     * @param array $defaultOpt 默认设置
     * @return array
     */
	public function getOptions($key, $itemKey = 'default', $defaultOpt = array()){
		$conf = Context::$appConfig->cache;

		if(isset($conf[$key])){
            $data = [];
            if (!is_array(current($conf[$key]))) {
                $data = $conf[$key];
            }

            if (is_array($conf[$key][$itemKey])) {
                $data = $conf[$key][$itemKey];
            }

			foreach($defaultOpt as $k => $v){
				if(!isset($data[$k])) {
                    $data[$k] = $v;
                }
			}

			return $data;
		}

		return $defaultOpt;
	}

    /**
     * __CALL魔术方法，在类没有对应方法时，尝试调研那个handler下的方法
     *
     * @param string $method
     * @param array $args
     * @return mixed
     * @throws \Exception
     */
    public function __call($method, $args){
		if(method_exists($this->handler, $method)){
		   return call_user_func_array(array($this->handler,$method), $args);
		}else{
			throw new \Exception("[Akari.data.BaseCacheAdapter] not found method");
		}
	}
}
