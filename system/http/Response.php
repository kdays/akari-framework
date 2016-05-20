<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:01
 */

namespace Akari\system\http;

use Akari\system\ioc\DIHelper;
use Akari\system\ioc\Injectable;
use Akari\system\result\Result;

Class Response extends Injectable{
    
    private $isSent = false;
    private $responseCode = HttpCode::OK;
    private $responseCodeMessage = NULL;
    
    private $headers = [];
    private $content;
    
    public function setStatusCode($code = HttpCode::OK, $msg = NULL) {
        $this->responseCode = $code;
        
        if ($code == HttpCode::UNAVAILABLE_FOR_LEGAL_REASON && $msg == NULL) {
            $msg = HttpCode::$statusCode[HttpCode::UNAVAILABLE_FOR_LEGAL_REASON];
        }
        $this->responseCodeMessage = $msg;
        
        return $this;
    }
    
    public function setContent($content) {
        $this->content = $content;
    }
    
    public function getContent() {
        return $this->content;
    }
    
    public function appendContent($content) {
        $this->content .= $content;
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
    
    public function resetHeaders() {
        $this->headers = [];
    }
    
    public function setHeaders($headers) {
        $this->headers += $headers;
        return $this;
    }

    public function setContentType($contentType = Result::CONTENT_HTML) {
        $this->setHeader('Content-Type', $contentType);
        
        return $this;
    }
    
    public function redirect($location, $statusCode = HttpCode::FOUND) {
        $this->setStatusCode($statusCode);
        $this->setHeader("location", $location);
    }
    
    public function sendHeaders() {
        if ($this->responseCodeMessage !== NULL) {
            header('HTTP/1.1 '. $this->responseCode. " ". $this->responseCodeMessage);
        } else {
            http_response_code($this->responseCode);
        }

        foreach ($this->headers as $key => $value) {
            header($key. ": ". $value);
        }
    }

    public function send() {
        $this->isSent = true;
        $this->sendHeaders();
        
        echo $this->content;
    }
    
    public function isSent() {
        return $this->isSent;
    }
    
    public function setCookie($name, $value, $expire = NULL, $useEncrypt = FALSE, $opts = []) {
        $this->cookies->set($name, $value, $expire, $useEncrypt, $opts);
    }
    
    public function removeCookie($name) {
        $this->cookies->remove($name);
    }
}