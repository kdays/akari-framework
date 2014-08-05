<?php
namespace Akari\system\http;

!defined("AKARI_PATH") && exit;

Class Request{
	private static $http = null;
	public static function getInstance(){
		if(self::$http == null){
			self::$http = new self();
		}
		
		return self::$http;
	}
	
	protected $requestMethod;
	protected $requestURI;
	protected $host;
	protected $remoteIP;
	protected $serverIP;
	protected $queryString;
	protected $accept;
	protected $referrer;
	protected $scriptName;
	protected $userAgent;
	protected $requestTime;
	protected $pathInfo;
	protected $parameters;
	
	/**
	 * 构造函数
	 */
	private function __construct(){
		$arr = Array(
			'requestMethod' => 'REQUEST_METHOD',
			'requestURI' => 'REQUEST_URI',
			'host' => 'HTTP_HOST',
			'remoteIP' => 'REMOTE_ADDR',
			'serverIP' => 'SERVER_ADDR',
			'serverPort' => 'SERVER_PORT',
			'queryString' => 'QUERY_STRING',
			'accept' => 'HTTP_ACCEPT',
			'referrer' => 'HTTP_REFERER',
			'scriptName' => 'SCRIPT_NAME',
			'userAgent' => 'HTTP_USER_AGENT',
			'pathInfo' => 'PATH_INFO'
		);
		
		foreach($arr as $key => $value){
			if(isset($_SERVER[$value])){
				$this->$key = $_SERVER[$value];
			}
		}
		
		$this->cookies = $_COOKIE;
		$this->requestTime = isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time();
		
		$this->parameters = $_REQUEST;
	}
	
	/**
	 * 获得用户IP
	 * @return string
	 */
	public function getUserIP(){
		$onlineip = $this->getRemoteIP();
		
		if ($_SERVER['HTTP_X_FORWARDED_FOR'] && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/',$_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$onlineip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		} elseif ($_SERVER['HTTP_CLIENT_IP'] && preg_match('/^([0-9]{1,3}\.){3}[0-9]{1,3}$/',$_SERVER['HTTP_CLIENT_IP'])) {
			$onlineip = $_SERVER['HTTP_CLIENT_IP'];
		}
		
		return $onlineip;
	}

    /**
     * 链接是否是SSL安全
     * @return bool
     */
    public function isSSL() {
		$protocol = strtoupper($_SERVER['SERVER_PROTOCOL']);
		if (isset($_SERVER['HTTPS'])) {
			return !!(strtoupper($_SERVER['HTTPS']) == 'ON');
		}

		return !!(strpos($protocol, 'HTTPS') !== FALSE);	
	}
	
	/**
	 * 获得请求的字符串
	 * @return string
	 */
	public function getQueryString() {
		return $this->queryString;
	}
	
	/**
	 * 是否存在这个参数
	 * @param string $name 名称
	 * @return bool
	 */
	public function hasParameter($name) {
		return array_key_exists($name, $this->parameters);
	}
	
	/**
	 * 获得参数
	 * @param string $name 参数
	 * @return NULL|string
	 */
	public function getParameter($name) {
		if (array_key_exists($name, $this->parameters)) {
			return $this->parameters[$name];
		}else{
			return null;
		}
	}
	
	/**
	 * 获得全部参数
	 * @return array
	 */
	public function getParameters() {
		return $this->parameters;
	}
	
	/**
	 * 获得PathInfo
	 * @return string
	 */
	public function getPathInfo(){
		return $this->pathInfo;
	}

    /**
     * @return int
     */
    public function getRequestTime() {
		return $this->requestTime;
	}
	
	/**
	 * 获得引用页路径
	 * @return string
	 */
	public function getReferrer(){
		return $this->referrer;
	}
	
	/**
	 * 获得远程IP
	 * @return string
	 */
	public function getRemoteIP() {
		return $this->remoteIP;
	}
	
	/**
	 * 获得服务器IP
	 * @return string
	 */
	public function getServerIP() {
		return $this->serverIP;
	}
	
	/**
	 * 获得请求的URI
	 * @return string
	 */
	public function getRequestURI() {
		return $this->requestURI;
	}
	
	/**
	 * 获得请求的脚本名称
	 * @return string
	 */
	public function getScriptName(){
		return $this->scriptName;
	}
	
	/**
	 * 获得useragent
	 * @return string
	 */
	public function getUserAgent(){
		return $this->userAgent;
	}
}