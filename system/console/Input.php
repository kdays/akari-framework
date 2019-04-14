<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-26
 * Time: 19:15
 */

namespace Akari\system\console;

class Input {

    protected $input;

    public function __construct($handle = 'php://stdin') {
        $this->input = fopen($handle, 'r');
    }

    public function __destruct() {
        // TODO: Implement __destruct() method.
        if ($this->input) {
            fclose($this->input);
        }
    }

    public function getInput() {
        return trim(fgets($this->input));
    }


}
