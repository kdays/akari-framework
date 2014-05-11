<?php
!defined("AKARI_PATH") && exit;

Class Cookie{
    public $flag = "|ENC";
	protected static $c;
	public static function getInstance() {
        if (self::$c == NULL){
            self::$c = new self();
        }
        return self::$c;
    }

    public function set($key, $value, $expire = NULL, $isEncrypt = FALSE, $option = array()){
        $config = Context::$appConfig;

        $expire = isset($expire) ? $expire : $config->cookieTime;
        if(is_numeric($expire)){
            $expire += time();
        }else{
            $expire = $expire=='now' ? 0 : strtotime($expire);
        }

        if(is_array($value)){
            $tmp = array();
            foreach($value as $k => $v) $tmp[] = "$k:$v";
            $value = implode("&", $tmp)."|A";
        }

        if($isEncrypt){
            $value = Security::encrypt($value, $config->cookieEncrypt).$this->flag;
        }

        $path = array_key_exists("path", $option) ? $option['path'] : $config->cookiePath;
        $domain  = array_key_exists("domain", $option) ? $option['domain'] : $config->cookieDomain;

        if($config->cookiePrefix)   $name = $config->cookiePrefix.$name;

        if($value === FALSE){
            setCookie($name, '', time() - 3600, $path, $domain);
        }else{
            setCookie($name, $value, $expire, $path, $domain);
        }
    }

    public function get($key, $encryptType = FALSE, $autoPrefix = true){
    	$config = Context::$appConfig;

        if(!in_string($name, $config->cookiePrefix) && $autoPrefix){
            $name = $config->cookiePrefix.$name;
        }

        if(!array_key_exists($name, $_COOKIE)) return NULL;
        $cookie = $_COOKIE[$name];

        if($encryptType != FALSE)   $cookie = Security::decrypt($cookie, $encryptType);

        $flag = $this->flag;
        $fLen = strlen($flag);

        if(substr($cookie, -$fLen, $fLen) == $flag){
            $cookie = substr($cookie, 0, -$fLen);
            $cookie = Security::decrypt($cookie, $config->cookieEncrypt);
        }

        //@todo: 如果最后2位是|A 代表是数组 
        if(substr($cookie, -2, 2) == '|A'){
            $result = array();
            $value = explode("&", substr($cookie, 0, sizeof($cookie) - 3));
            foreach($value as $v){
                list($key, $val) = explode(":", $v);
                $result[ $key ] = $val;
            }

            return $result;
        }

        return $cookie;
    }
}