<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:09
 */

namespace Akari\system\console;

Class ConsoleInput {

    protected $input;

    public function __construct($handle = 'php://stdin') {
        $this->input = fopen($handle, 'r');
    }

    public function read() {
        return fgets($this->input);
    }
}