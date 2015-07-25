<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/21
 * Time: 上午9:39
 */

namespace Akari\system\tpl\engine\shinobu;


class ShinoToken {

    const TYPE_TEXT = 0;
    const TYPE_VAR = 1;
    const TYPE_VAR_END = 2;
    const TYPE_OPER = 3;

    const TYPE_BLOCK = 5;
    const TYPE_BLOCK_END = 6;

    const TYPE_STR = 10;
    const TYPE_NAME = 11;
    const TYPE_PUNCTUATION = 12;
    const TYPE_NUM = 13;

    protected $type;
    protected $value;
    protected $line;

    public function __construct($type, $value, $line) {
        $this->type = $type;
        $this->value = $value;
        $this->line = $line;

        return $this;
    }

}