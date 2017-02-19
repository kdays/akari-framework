<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/27
 * Time: 下午2:13.
 */

namespace Akari\system\exception;

class FatalException extends \Exception
{
    public $logLevel = AKARI_LOG_LEVEL_FATAL;

    protected $type;

    public function __construct($message, $file, $line, $type)
    {
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->type = $type;
    }
}
