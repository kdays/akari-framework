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
    
    private static $instance = null;
    public static function getInstance(){
        if(self::$instance == null){
            self::$instance = new self();
        }

        return self::$instance;
    }

    protected function __construct() {
        $this->output = new ConsoleOutput();
    }
    
    public function message($message, $style = NULL) {
        if (!empty($style)) {
            $message = "<$style>$message</$style>";
        }
        return $this->output->write($message);
    }
    
}