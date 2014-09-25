<?php
namespace Akari\system\log;

use Akari\Context;

!defined("AKARI_PATH") && exit;

Class FileLogger{
	private static $l;
	public static $opt = array();
	private $f;

	public static function getInstance($opt){
		self::$opt = $opt;
		if (self::$l == null) {
            self::$l = new self();
        }
        return self::$l;
	}

    public function __construct() {
    	$filename = Context::$appBasePath."/".self::$opt['filename'];
        $checkDir = FALSE;

        $dir = dirname($filename);
        if (!is_dir($dir)) {
            $checkDir = mkdir($dir);
        } else {
            $checkDir = TRUE;
        }
        
        if ($checkDir) {
            $this->logFile = $filename;
        }
    }
    
    public function append($msg, $level) {
        if (!$this->f) {
            @$this->f = fopen($this->logFile, 'a');
            @chmod($this->logFile, 0777);
        }
        
        @flock($this->f, LOCK_EX);
        @fwrite($this->f, date('[Y-m-d H:i:s] ') . $msg . "\n");
        @flock($this->f, LOCK_UN);
    }

    public function __destruct(){
        @fclose($this->f);
    }
}