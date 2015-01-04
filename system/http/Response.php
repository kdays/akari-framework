<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:01
 */

namespace Akari\system\http;

use Akari\system\result\Result;

Class Response {

    protected static $h;

    private $responseCode = HttpCode::OK;
    private $headers = [];

    public static function getInstance() {
        if (!isset(self::$h)) {
            self::$h = new self();
        }
        return self::$h;
    }

    public function setStatusCode($code = HttpCode::OK) {
        $this->responseCode = $code;
    }

    public function setHeader($key, $value) {
        $this->headers[$key] = $value;
    }

    public function setContentType($contentType = Result::CONTENT_HTML) {
        $this->setHeader('Content-Type', $contentType);
    }

    public function doOutput() {
        http_response_code($this->responseCode);
        foreach ($this->headers as $key => $value) {
            Header($key. ": ". $value);
        }
    }

}