<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/1/1
 * Time: 16:15
 */

namespace Akari\utility;

use Akari\system\exception\AkariException;

class CURL {
    protected $handler;
    protected $options = array(
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        CURLOPT_USERAGENT => "Akari/1.0",
        CURLOPT_SSL_VERIFYPEER => FALSE,
        CURLOPT_SSL_VERIFYHOST => FALSE
    );

    /**
     * 构造函数
     *
     * @throws CURLException
     */
    public function __construct() {
        if(!function_exists("curl_init")){
            throw new CURLException("curl not installed");
        }
    }

    /**
     * 关闭连接
     *
     * @return curl
     */
    public function close() {
        if($this->handler){
            curl_close($this->handler);
            $this->handler = NULL;
        }

        return $this;
    }

    public static function createCurlFile($filename) {
        if (function_exists('curl_file_create')) {
            return curl_file_create($filename);
        }

        return "@$filename;filename=" . basename($filename);
    }

    /**
     * 执行请求
     *
     * @param string $url URL地址
     * @param array $options CURL的额外设置
     * @throws CURLException
     * @return mixed
     */
    public function execute($url, $options = array()) {
        $this->close();

        $opts = $this->options;
        foreach($options as $key => $value){
            $opts[$key] = $value;
        }
        $opts[CURLOPT_URL] = $url;

        $handler = curl_init();
        curl_setopt_array($handler, $opts);
        $result = curl_exec($handler);
        if($result === FALSE){
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
    public function getInfo($info = NULL) {
        if(!$this->handler) return FALSE;

        return $info == NULL ? curl_getinfo($this->handler) : curl_getinfo($this->handler, $info);
    }

    /**
     * 发送请求
     *
     * @param string $url 地址
     * @param string $method 请求类型(GET/POST/PUT..)
     * @param array $params 参数
     * @return CUrlResponseObj {header, body, info}
     */
    public function send($url, $method, $params = array()) {
        $method = strtoupper($method);
        $params = empty($params) ? NULL : http_build_query($params);

        $options = array();

        if($method == 'GET' || $method == 'HEAD'){
            if ($params) {
                $url .= strpos($url, "?") ? "&" : "?";
                $url .= $params;
            }
            
            if($method == 'GET'){
                $options[CURLOPT_HTTPGET] = TRUE;
            }else{
                $options[CURLOPT_NOBODY] = TRUE;
            }
        }else{
            if($method == 'POST'){
                $options[CURLOPT_POST] = TRUE;
            }else{
                $options[CURLOPT_CUSTOMREQUEST] = $method;
            }

            if($params)  $options[CURLOPT_POSTFIELDS] = $params;
        }

        $options[CURLOPT_HEADER] = TRUE;

        $result = $this->execute($url, $options);

        $message = new CUrlResponseObj();
        $message->info = $this->getInfo();

        $header_size = $message->info['header_size'];
        $message->header = preg_split('/\r\n/', substr($result, 0, $header_size), 0, PREG_SPLIT_NO_EMPTY);

        $message->body = substr($result, $header_size);
        $this->close();

        return $message;
    }


    protected static function buildCookies($data) {
        $cookie = '';
        foreach( $data as $k => $v ) {
            $cookie[] = $k . '=' . $v;
        }

        if( count( $cookie ) > 0 ) {
            return trim( implode( '; ', $cookie ) );
        }

        return '';
    }

    protected static function getHandler(array $opts) {
        $handler = new self();

        $optMap = [
            'timeout' => CURLOPT_TIMEOUT,
            'follow' => CURLOPT_FOLLOWLOCATION,
            'cookie' => CURLOPT_COOKIE,
            'range' => CURLOPT_RANGE,
            'user-agent' => CURLOPT_USERAGENT,
            'referer' => CURLOPT_REFERER
        ];

        foreach ($opts as $k => $v) {
            if (isset($optMap[$k])) {
                $k = $optMap[$k];
                if ($k == 'cookie' && is_array($v)) {
                    $v = self::buildCookies($v);
                }
            }

            $handler->options[$k] = $v;
        }

        return $handler;
    }

    /**
     * 发送GET请求
     * @param string $url URL地址
     * @param array $params 参数
     * @param array $opts
     * @return CUrlResponseObj 结果数组中header为头部信息 info为curl的信息 body为内容
     */
    public static function get($url, array $params, $opts = []) {
        return self::getHandler($opts)->send($url, 'GET', $params);
    }

    /**
     * 发送POST请求
     * @param string $url URL地址
     * @param array $params 参数
     * @param array $opts
     * @return CUrlResponseObj 结果数组中header为头部信息 info为curl的信息 body为内容
     */
    public static function post($url, array $params, $opts = []) {
        return self::getHandler($opts)->send($url, 'POST', $params);
    }

    /**
     * 获得头部信息
     * @param string $url URL地址
     * @param array $params 参数
     * @param array $opts
     * @return CUrlResponseObj 结果数组中header为头部信息 info为curl的信息 body为空
     */
    public static function head($url, array $params, $opts = []) {
        return self::getHandler($opts)->send($url, 'HEAD', $params);
    }
}

class CURLException extends AkariException {
    /**
     * @param string $message
     * @param int $code
     * @param string $requestURL
     */
    public function __construct($message, $code = NULL, $requestURL = "") {
        $this->message = "CURL Access Failed: " . $message;
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

class CUrlResponseObj {

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

    /**
     * 获得请求返回的httpCode
     *
     * @return bool|int
     */
    public function getCode() {
        return isset($this->info['http_code']) ? $this->info['http_code'] : FALSE;
    }

    /**
     * 获得实际访问地址
     *
     * @return bool|string
     */
    public function getUrl() {
        return isset($this->info['url']) ? $this->info['url'] : FALSE;
    }

    /**
     * 获得Content-Type
     *
     * @return bool|string
     */
    public function getContentType() {
        return isset($this->info['content_type']) ? $this->info['content_type'] : FALSE;
    }

    /**
     * 获得服务器IP
     *
     * @return bool|string
     */
    public function getRemoteIP() {
        return isset($this->info['primary_ip']) ?$this->info['primary_ip'] : FALSE;
    }

    /**
     * 获得服务器返回的头部
     *
     * @return array
     */
    public function getHeaders() {
        return $this->header;
    }

    /**
     * 获得请求主体，method=HEAD返回NULL
     *
     * @return NULL|string
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * 将请求结果保存到文件
     *
     * @param $target
     */
    public function save($target) {
        FileHelper::write($target, $this->getBody());
    }

    /**
     * 获得服务器设置的Cookies列表
     *
     * @return array
     */
    public function getCookies() {
        return $this->parseCookie($this->header);
    }

    /**
     * 获得服务器设置的某个Cookie，没有返回NULL
     *
     * @param $key
     * @return null|array
     */
    public function getCookie($key) {
        $cookies = $this->getCookies();
        if (isset($cookies[$key])) {
            return $cookies[$key];
        }

        return NULL;
    }

    /**
     * @param $header
     * @return array
     */
    private function parseCookie($header) {
        $cookies = array();
        foreach( $header as $line ) {
            if( !preg_match( '/^Set-Cookie: /i', $line ) ) {
                continue;
            }

            $line = preg_replace( '/^Set-Cookie: /i', '', trim( $line ) );
            $csplit = explode( ';', $line );
            $cdata = array();
            foreach( $csplit as $data ) {
                $cinfo = explode('=', $data);
                $cinfo[0] = trim($cinfo[0]);
                if ($cinfo[0] == 'expires') $cinfo[1] = strtotime($cinfo[1]);
                if ($cinfo[0] == 'secure') $cinfo[1] = "true";

                if (in_array($cinfo[0], array('domain', 'expires', 'path', 'secure', 'comment'))) {
                    $cdata[trim($cinfo[0])] = $cinfo[1];
                } else {
                    $cdata['key'] = $cinfo[0];
                    $cdata['value'] = $cinfo[1];
                }
            }

            $cookies[ $cdata['key'] ] = $cdata;
        }

        return $cookies;
    }



}
