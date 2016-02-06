<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:09
 */

namespace Akari\system\console;

Class Response {

    protected $output;

    public function __construct() {
        $this->output = new ConsoleOutput();
    }
    
    public function message($message, $style = NULL) {
        if (!empty($style)) {
            $message = "<$style>$message</$style>";
        }
        return $this->output->write($message);
    }
    
}