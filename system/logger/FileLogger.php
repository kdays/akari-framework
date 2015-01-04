<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:12
 */

namespace Akari\system\logger;

use Akari\Context;

Class FileLogger {

    protected $handler;
    protected $logFile;
    public $opt = [
    ];

    public static $l;

    public static function getInstance(array $opt){
        if (self::$l == null) {
            self::$l = new self($opt);
        }
        return self::$l;
    }

    public function __construct($opts = []) {
        foreach ($opts as $key => $value) {
            $this->opt[$key] = $value;
        }

        $path = Context::$appBasePath. DIRECTORY_SEPARATOR. $this->opt['filename'];
        $baseDir = dirname($path);

        if (!is_dir($baseDir)) {
            mkdir($baseDir, TRUE);
        }

        $this->logFile = $path;
    }

    public function append($msg, $level) {
        if (!$this->handler) {
            @$this->handler = fopen($this->logFile, 'a');
            @chmod($this->logFile, 0777);
        }

        @flock($this->handler, LOCK_EX);
        @fwrite($this->handler, date('[Y-m-d H:i:s] ') . $msg . "\n");
        @flock($this->handler, LOCK_UN);
    }

    public function __destruct(){
        @fclose($this->handler);
    }
}