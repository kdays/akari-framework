<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/5/17
 * Time: 下午2:44
 */

namespace Akari\system\exception;

class PHPErrorException extends \Exception {
    
    protected $context;

    public function __construct(\Error $error) {
        $this->message = $error->getMessage();
        $this->file = $error->getFile();
        $this->line = $error->getLine();
        $this->context = $error;
    }
    
    public function getTraceString() {
        return $this->context->getTraceAsString();
    }
    
    public function getMagicTrace() {
        return $this->context->getTrace();
    }

}
