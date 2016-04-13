<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:01
 */

namespace Akari\system\http;

use Akari\system\ioc\DIHelper;
use Akari\system\result\Result;

Class Response {
    
    use DIHelper;
    
    private $responseCode = HttpCode::OK;
    private $headers = [];
    

    public function setStatusCode($code = HttpCode::OK) {
        $this->responseCode = $code;
        
        return $this;
    }

    public function useNoCache() {
        $this->setHeader('Pragma', 'no-cache');
        $this->setHeader('Cache-Control', 'no-cache');
        
        return $this;
    }

    public function setCacheTime($time) {
        if (!is_numeric($time)) {
            $time = strtotime($time);
        }

        $this->setHeader("Cache-Control", "max-age=". $time);
        
        return $this;
    }

    public function setHeader($key, $value) {
        $this->headers[$key] = $value;
        
        return $this;
    }

    public function setContentType($contentType = Result::CONTENT_HTML) {
        $this->setHeader('Content-Type', $contentType);
        
        return $this;
    }

    public function send() {
        http_response_code($this->responseCode);
        foreach ($this->headers as $key => $value) {
            header($key. ": ". $value);
        }
    }
    
    public function setCookie($name, $value, $expire = NULL, $useEncrypt = FALSE, $opts = []) {
        /** @var Cookie $cookie */
        $cookie = $this->_getDI()->getShared('cookies');
        $cookie->set($name, $value, $expire, $useEncrypt, $opts);
    }
    
    public function removeCookie($name) {
        /** @var Cookie $cookie */
        $cookie = $this->_getDI()->getShared('cookies');
        $cookie->remove($name);
    }
}