<?php
!defined("AKARI_PATH") && exit;

Class BaseCacheAdapter{
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

	/**
	 * 获得缓存配置
	 * @param string $key 项目
	 * @param array $defaultOpt 默认设置
	 **/
	public function getOptions($key, $defaultOpt = array()){
		$conf = Context::$appConfig->cache;

		if(isset($conf[$key])){
			$data = $conf[$key];
			foreach($defaultOpt as $k => $v){
				if(!isset($data[$k]))	$data[$k] = $v;
			}

			return $data;
		}

		return $defaultOpt;
	}

	/**
	 * __CALL魔术方法，在没有类的方法时 尝试调用handler下的方法
	 */
	public function __call($method, $args){
		if(method_exists($this->handler, $method)){
           return call_user_func_array(array($this->handler,$method), $args);
        }else{
        	throw new Exception("METHOD NOT FOUND");
        }
	}
}