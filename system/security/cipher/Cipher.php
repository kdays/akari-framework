<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/31
 * Time: 下午9:29
 */

namespace Akari\system\security\cipher;

abstract class Cipher {

    protected $options = [];

    public function __construct(array $opts = []) {
        $this->options = $opts;
    }

    protected function setOption($key, $value) {
        $this->options[$key] = $value;
    }

    protected function getOption($key, $defaultValue = FALSE) {
        return array_key_exists($key, $this->options) ? $this->options[$key] : $defaultValue;
    }

    abstract public function encrypt($text);
    abstract public function decrypt($text);

}
