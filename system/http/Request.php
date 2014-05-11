<?php
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
	 *
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
     * @return string
     */
    public function getQueryString() {
        return $this->queryString;
    }
    
    /**
     * @name name
     * @return bool
     */
    public function hasParameter($name) {
        return array_key_exists($name, $this->parameters);
    }
    
    /**
     * @name name
     * @return string
     */
    public function getParameter($name) {
        if (array_key_exists($name, $this->parameters)) {
            return $this->parameters[$name];
        }else{
            return null;
        }
    }
    
    /**
     * @return array
     */
    public function getParameters() {
        return $this->parameters;
    }
	
	public function getPathInfo(){
		return $this->pathInfo;
	}
	
	public function getRequestTime() {
        return $this->requestTime;
    }
    
    public function getReferrer(){
    	return $this->referrer;
    }
    
    public function getRemoteIP() {
        return $this->remoteIP;
    }
    
    public function getServerIP() {
        return $this->serverIP;
    }
    
    public function getRequestURI() {
        return $this->requestURI;
    }
	
	public function getScriptName(){
		return $this->scriptName;
	}
	
	public function getUserAgent(){
		return $this->userAgent;
	}
}