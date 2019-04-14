<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/19
 * Time: 21:42
 */

namespace Akari\system\http;


use Akari\system\ioc\Injectable;

class Response {

    private $isSent = FALSE;
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

    public function getStatusCode() {
        return $this->responseCode;
    }

    public function setContent($content) {
        $this->content = $content;
    }

    public function setJsonContent($content) {
        $this->content = json_encode($content);
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

        $this->setHeader("Cache-Control", "max-age=" . $time);

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

    public function setContentType($contentType = 'text/html') {
        $this->setHeader('Content-Type', $contentType);

        return $this;
    }

    public function redirect($location, $statusCode = HttpCode::FOUND) {
        $this->setStatusCode($statusCode);
        $this->setHeader("location", $location);
    }

    public function sendHeaders() {
        if ($this->responseCodeMessage !== NULL) {
            header('HTTP/1.1 ' . $this->responseCode . " " . $this->responseCodeMessage);
        } else {
            http_response_code($this->responseCode);
        }

        foreach ($this->headers as $key => $value) {
            header($key . ": " . $value);
        }
    }

    public function send() {
        $this->isSent = TRUE;
        $this->sendHeaders();

        echo $this->content;
    }

    public function isSent() {
        return $this->isSent;
    }

}
