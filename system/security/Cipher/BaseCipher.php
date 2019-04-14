<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:48
 */

namespace Akari\system\security\cipher;

abstract class BaseCipher {

    protected $options = [];
    public function __construct(array $options) {
        $this->options = $options;
    }

    public function getOption(string $key, $defaultValue = NULL) {
        return $this->options[$key] ?? $defaultValue;
    }

    abstract public function encrypt($text);
    abstract public function decrypt($text);

}