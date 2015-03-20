<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/3/20
 * Time: 上午11:35
 */

namespace Akari\system\router;

class URL {

    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_PUT = "PUT";
    const METHOD_DELETE = "DELETE";
    const METHOD_GP = "GP";

    protected $url;
    protected $callback;
    protected $method;

    public function __construct($url, $callback, $method = NULL) {
        $this->url = $url;
        $this->callback = $callback;
        $this->method = $method;
    }

    public function getCallback() {
        return $this->callback;
    }

    public function makeRegExp() {
        return $this->method. ":". $this->url;
    }

}