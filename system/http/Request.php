<?php
namespace Akari\system\http;

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

    // http://cn2.php.net/manual/en/function.ip2long.php#104163
    private function bin2ip($bin) {
        if(strlen($bin) <= 32) // 32bits (ipv4)
            return long2ip(base_convert($bin,2,10));
        if(strlen($bin) != 128)
            return false;
        $pad = 128 - strlen($bin);
        for ($i = 1; $i <= $pad; $i++)
        {
            $bin = "0".$bin;
        }
        $bits = 0;
        while ($bits <= 7)
        {
            $bin_part = substr($bin,($bits*16),16);
            $ipv6 .= dechex(bindec($bin_part)).":";
            $bits++;
        }
        return inet_ntop(inet_pton(substr($ipv6,0,-1)));
    }

    /**
     * 获得用户IP
     * @return string
     */
    public function getUserIP() {
        $onlineIp = $this->getRemoteIP();

        // try ipv6 => ipv4
        if (filter_var($onlineIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $onlineIp = $this->bin2ip(base_convert(ip2long($onlineIp), 10, 2));
        }

        // 如果onlineip是内网ip
        $isInternal = false;
        $ip_address = explode(".", $onlineIp);
        if ($ip_address[0] == 10) {
            $isInternal = true;
        } elseif ($ip_address[0] == 172 && $ip_address[1] > 15 && $ip_address[1] < 32) {
            $isInternal = true;
        } elseif ($ip_address[0] == 192 && $ip_address[1] == 168) {
            $isInternal = true;
        }

        if ($isInternal && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $onlineIp = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        if ($onlineIp && strstr($onlineIp, ',')) {
            $x = explode(',', $onlineIp);
            $onlineIp = end($x);
        }

        if (!filter_var($onlineIp, FILTER_VALIDATE_IP)) {
            $onlineIp = '0.0.0.0';
        }

        return $onlineIp;
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
     *
     * @param string $name 参数
     * @param null $defaultValue
     * @return NULL|string
     */
    public function getParameter($name, $defaultValue = NULL) {
        if (array_key_exists($name, $this->parameters)) {
            return $this->parameters[$name];
        }else{
            return $defaultValue;
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

    public function getFullURI() {
        return $this->host. $this->requestURI;
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

    /**
     * 获得请求模式 (PUT/GET/POST)
     * @return mixed
     */
    public function getRequestMethod() {
        return $this->requestMethod;
    }
}