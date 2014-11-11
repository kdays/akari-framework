<?php
namespace Akari\utility;

Class curl{
	protected $handler;
	protected $options = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_TIMEOUT => 5,
		CURLOPT_USERAGENT => "Akari/1.0",
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_SSL_VERIFYHOST => false
	);
	
	/**
	 * 构造函数
	 * 
	 * @throws CURLException
	 */
	public function __construct(){
		if(!function_exists("curl_init")){
			throw new CURLException("curl not installed");
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
			throw new CURLException(curl_error($handler), curl_errno($handler), $url);
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
	 * @return CUrlResponseObj {header, body, info}
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
		
		$message = new CUrlResponseObj();
		$message->info = $this->getInfo();
		
		$header_size = $message->info['header_size'];
		$message->header = preg_split('/\r\n/', substr($result, 0, $header_size), 0, PREG_SPLIT_NO_EMPTY);
		
		$message->body = substr($result, $header_size);
		
		return $message;
	}
	
	/**
	 * 发送GET请求
	 * @param string $url URL地址
	 * @param array $params 参数
	 * @return CUrlResponseObj 结果数组中header为头部信息 info为curl的信息 body为内容
	 */
	public function get($url, $params = array()){
		return $this->send($url, 'GET', $params);
	}
	
	/**
	 * 发送POST请求
	 * @param string $url URL地址
	 * @param array $params 参数
	 * @return CUrlResponseObj 结果数组中header为头部信息 info为curl的信息 body为内容
	 */
	public function post($url, $params = array()){
		return $this->send($url, 'POST', $params);
	}
	
	/**
	 * 获得头部信息
	 * @param string $url URL地址
	 * @param array $params 参数
	 * @return CUrlResponseObj 结果数组中header为头部信息 info为curl的信息 body为空
	 */
	public function head($url, $params = array()){
		return $this->send($url, 'HEAD', $params);
	}
}

Class CURLException extends \Exception{
	/**
	 * @param string $message
	 * @param int $code
	 * @param string $requestURL
	 */
	public function __construct($message, $code = NULL, $requestURL = "") {
		$this->message = "CURL ERROR: ". $message;
		$this->code = $code;

		// 如果指示当前的CURL类没有任何意义
		if (!empty($requestURL)) {
			$trace = debug_backtrace();
			if (isset($trace[3])) {
				$this->file = $trace[3]['file'];
				$this->line = $trace[3]['line'];
			}
		}
	}
}

Class CUrlResponseObj {
	/**
	 * Http头信息
	 * @var array
	 */
	public $header;

	/**
	 * 请求的内容信息，在使用head时为null
	 * @var string|NULL
	 */
	public $body;

	/**
	 * CURL一些请求到的资料，如http_code
	 * @var array
	 */
	public $info;
}