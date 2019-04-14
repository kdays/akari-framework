<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-08
 * Time: 14:32
 */

namespace Akari\exception;

class PHPFatalException extends \Exception {

    protected $type;

    public function __construct(string $message, string $file, int $line, int $type) {
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->type = $type;
    }

    public function getType() {
        return $this->type;
    }

}
