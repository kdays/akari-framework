<?php
namespace Akari\system\http;

Class Request{
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

    /**
     * 构造函数
     */
    public function __construct(){
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
    }

    // http://cn2.php.net/manual/en/function.ip2long.php#104163
    private function bin2ip($bin) {
        if(strlen($bin) <= 32) {
            return long2ip(base_convert($bin,2,10));
        } // 32bits (ipv4)

        if(strlen($bin) != 128) {
            return false;
        }

        $pad = 128 - strlen($bin);
        for ($i = 1; $i <= $pad; $i++) {
            $bin = "0". $bin;
        }

        $ipv6 = "";
        $bits = 0;
        while ($bits <= 7) {
            $bin_part = substr($bin, ($bits * 16), 16);
            $ipv6 .= dechex(bindec($bin_part)).":";
            $bits++;
        }

        return inet_ntop(inet_pton(substr($ipv6, 0, -1)));
    }

    /**
     * 获得用户IP
     * @return string
     */
    public function getUserIP() {
        $onlineIp = $this->getRemoteIP();

        // try ipv6 => ipv4
        if (filter_var($onlineIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $ipv4Addr = $this->bin2ip(base_convert(ip2long($onlineIp), 10, 2));
            if ($ipv4Addr) $onlineIp = $ipv4Addr;
        }

        // 如果onlineip是内网ip
        $isInternal = false;
        $ipAddr = explode(".", $onlineIp);
        if ($ipAddr[0] == 10) {
            $isInternal = true;
        } elseif ($ipAddr[0] == 172 && $ipAddr[1] > 15 && $ipAddr[1] < 32) {
            $isInternal = true;
        } elseif ($ipAddr[0] == 192 && $ipAddr[1] == 168) {
            $isInternal = true;
        }

        if ($isInternal && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $onlineIp = $_SERVER['HTTP_X_FORWARDED_FOR'];

            if ($onlineIp && strstr($onlineIp, ',')) {
                $x = explode(',', $onlineIp);
                $onlineIp = end($x);
            }
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
        return array_key_exists($name, $_REQUEST);
    }

    /**
     * 获得参数
     *
     * @param string $name 参数
     * @param null $defaultValue
     * @return NULL|string
     */
    public function getParameter($name, $defaultValue = NULL) {
        if (array_key_exists($name, $_REQUEST)) {
            return $_REQUEST[$name];
        }else{
            return $defaultValue;
        }
    }

    /**
     * 获得全部参数
     * @return array
     */
    public function getParameters() {
        return $_REQUEST;
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
        return strtoupper($this->requestMethod);
    }

    /**
     * 是否是POST请求
     *
     * @return bool
     */
    public function isPost() {
        return $this->getRequestMethod() == 'POST';
    }

    /**
     * @return bool
     */
    public function isGet() {
        return $this->getRequestMethod() == 'GET';
    }

    /**
     * @param string $key
     * @param null|mixed $defaultValue
     * @return NULL|string
     */
    public function getQuery($key, $defaultValue = NULL) {
        return GP($key, 'GP', $defaultValue);
    }

    /**
     * @param string $key
     * @param null $defaultValue
     * @return NULL|string
     */
    public function getPost($key, $defaultValue = NULL) {
        return GP($key, 'P', $defaultValue);
    }

    /**
     * 是否是Ajax请求
     * @return bool
     */
    public function isAjax() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_SERVER['HTTP_SEND_BY']);
    }

    /**
     * 是否是移动设备
     * @return bool
     */
    public function isMobileDevice() {
        if ($this->isIOSDevice()) {
            return True;
        }

        $ua = $this->getUserAgent();
        $keyword = ["ucweb", "Windows Phone", "android", "opera mini", "blackberry"];
        foreach ($keyword as $value) {
            if (preg_match("/$value/i", $ua)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return string
     */
    public function getRawBody() {
        return file_get_contents('php://input');
    }

    /**
     * 是否是iphone
     * @return bool
     */
    public function isIOSDevice(){
        $ua = $this->getUserAgent();

        return (preg_match('/ipod/i', $ua) || preg_match('/iphone/i', $ua));
    }

    /**
     * 判断是否是微信的
     * @return bool
     */
    public function isInWeChat() {
        $ua = $this->getUserAgent();
        return (preg_match('/MicroMessenger/i', $ua) || preg_match('/Window Phone/i', $ua));
    }

    /**
     * 获得所有文件上传
     * @return FileUpload[]
     */
    public function getUploadedFiles() {
        $files = [];

        foreach ($_FILES as $key => $now) {
            if ($now['error'] == UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $files[] = new FileUpload($now, $key);
        }

        return $files;
    }

    /**
     * @param string $name
     * @return FileUpload|bool|null
     */
    public function getUploadedFile($name) {
        if (isset($_FILES[$name])) {
            // 没有文件的话返回false 其余让用户判定
            if ($_FILES[$name]['error'] == UPLOAD_ERR_NO_FILE) {
                return false;
            }
            return new FileUpload($_FILES[$name], $name);
        }

        return NULL;
    }
    
    public function hasCookie($name) {
        return Cookie::getInstance()->has($name);
    }
    
    public function getCookie($name, $autoPrefix = TRUE) {
        return Cookie::getInstance()->get($name, $autoPrefix);
    }
}