<?php
Class curl{
	protected $handler;
	protected $options = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 5,
		CURLOPT_USERAGENT => "Akari/1.0"
	);
	
	/**
	 * 构造函数
	 * 
	 * @throws Exception
	 */
	public function __construct(){
		if(!function_exists("curl_init")){
			throw new Exception("no CURL");
		}
	}
	
	/**
	 * 关闭连接
	 * 
	 * @return curl
	 */
	public function close(){
		if($this->handler){
			curl_close($this->handler);
			$this->handler = null;
		}
		
		return $this;
	}
	
	/**
	 * 执行请求
	 * 
	 * @param string $url URL地址
	 * @param array $options CURL的额外设置
	 * @throws CURLException
	 * @return mixed
	 */
	public function execute($url, $options = Array()) {
		$this->close();
		
		$opts = $this->options;
		foreach($options as $key => $value){
			$opts[$key] = $value;
		}
		$opts[CURLOPT_URL] = $url;
		
		$handler = curl_init();
		curl_setopt_array($handler, $opts);
		$result = curl_exec($handler);
		if($result === false){
			throw new CURLException("CURL ERROR: ".curl_error($handler), curl_errno($handler));
		}
		
		$this->handler = $handler;
		
		return $result;
	}
	
	/**
	 * 获得统计信息(不是头部信息)
	 * 
	 * @param string $info
	 * @return boolean|mixed
	 */
	public function getInfo($info = null){
		if(!$this->handler) return false;
		
		return $info==null ? curl_getinfo($this->handler) : curl_getinfo($this->handler, $info);
	}
	
	/**
	 * 发送请求
	 * 
	 * @param string $url 地址
	 * @param string $method 请求类型(GET/POST/PUT..)
	 * @param array $params 参数
	 * @return multitype:multitype: string Ambigous <boolean, mixed>
	 */
	public function send($url, $method, $params = array()){
		$method = strtoupper($method);
		$params = empty($params) ? NULL : http_build_query($params);
		
		$options = array();
		
		if($method == 'GET' || $method == 'HEAD'){
			$url .= strpos($url, "?") ? "&" : "?";
			$url .= $params;
			
			if($method == 'GET'){
				$options[CURLOPT_HTTPGET] = true;
			}else{
				$options[CURLOPT_NOBODY] = true;
			}
		}else{
			if($method == 'POST'){
				$options[CURLOPT_POST] = true;
			}else{
				$options[CURLOPT_CUSTOMREQUEST] = $method;
			}
			
			if($params)  $options[CURLOPT_POSTFIELDS] = $params;
		}
		
		$options[CURLOPT_HEADER] = true;
		
		$result = $this->execute($url, $options);
		
		$message = array();
		$message['info'] = $this->getInfo();
		
		$header_size = $message['info']['header_size'];
		$message['header'] = preg_split('/\r\n/', substr($result, 0, $header_size), 0, PREG_SPLIT_NO_EMPTY);
		
		$message['body'] = substr($result, $header_size);
		
		return $message;
	}
	
	/**
	 * 发送GET请求
	 * @param string $url URL地址
	 * @param array $params 参数
	 * @return array 结果数组中header为头部信息 info为curl的信息 body为内容
	 */
	public function get($url, $params = array()){
		return $this->send($url, 'GET', $params);
	}
	
	/**
	 * 发送POST请求
	 * @param string $url URL地址
	 * @param array $params 参数
	 * @return array 结果数组中header为头部信息 info为curl的信息 body为内容
	 */
	public function post($url, $params = array()){
		return $this->send($url, 'POST', $params);
	}
	
	/**
	 * 获得头部信息
	 * @param string $url URL地址
	 * @param array $params 参数
	 * @return array 结果数组中header为头部信息 info为curl的信息 body为空
	 */
	public function head($url, $params = array()){
		return $this->send($url, 'HEAD', $params);
	}
}

Class CURLException extends Exception{
	
}