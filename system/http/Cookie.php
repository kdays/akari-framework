<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:05
 */

namespace Akari\system\http;

use Akari\Context;
use Akari\system\security\Security;

Class Cookie {

    const FLAG_ENCRYPT = "|ENC";
    const FLAG_ARRAY = "|A";
    const SPLIT = "\x00";

    protected static $c;
    public static function getInstance() {
        if (self::$c == NULL){
            self::$c = new self();
        }
        return self::$c;
    }

    public function set($name, $value, $expire = NULL, $encrypt = FALSE, $opts = []) {
        $config = Context::$appConfig;

        $expire = isset($expire) ? $expire : $config->cookieTime;
        if(is_numeric($expire)){
            $expire += time();
        }else{
            $expire = $expire=='now' ? 0 : strtotime($expire);
        }

        if(is_array($value)){
            $tmp = array();
            foreach($value as $k => $v) $tmp[] = "$k".self::SPLIT."$v";
            $value = implode("&", $tmp).self::FLAG_ARRAY;
        }

        if ($encrypt) $value = Security::encrypt($value, 'cookie');
        $path = array_key_exists("path", $opts) ? $opts['path'] : $config->cookiePath;
        $domain  = array_key_exists("domain", $opts) ? $opts['domain'] : $config->cookieDomain;

        if($config->cookiePrefix)   $name = $config->cookiePrefix.$name;

        if($value === FALSE){
            setCookie($name, '', time() - 3600, $path, $domain);
        }else{
            setCookie($name, $value, $expire, $path, $domain);
        }
    }

    public function get($name, $autoPrefix = TRUE) {
        $config = Context::$appConfig;

        if(!in_string($name, $config->cookiePrefix) && $autoPrefix){
            $name = $config->cookiePrefix.$name;
        }

        if(!array_key_exists($name, $_COOKIE)) return NULL;
        $cookie = $_COOKIE[$name];

        $len = strlen(self::FLAG_ENCRYPT);
        if (substr($cookie, -$len, $len) == self::FLAG_ENCRYPT) {
            $cookie = Security::decrypt(substr($cookie, 0, $len), 'cookie');
        }

        if(substr($cookie, -2, 2) == self::FLAG_ARRAY){
            $result = array();
            $arrayFlagLen = strlen(self::FLAG_ARRAY) + 1;

            $value = explode("&", substr($cookie, 0, sizeof($cookie) - $arrayFlagLen));
            foreach($value as $v){
                list($key, $val) = explode(self::SPLIT, $v);
                $result[ $key ] = $val;
            }

            return $result;
        }

        return $cookie;
    }

    public function remove($key) {
        $this->set($key, FALSE);
    }

}