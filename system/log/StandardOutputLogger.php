<?php
namespace Akari\system\log;

use Akari\system\console\ConsoleOutput;

!defined("AKARI_PATH") && exit;

Class StandardOutputLogger{
	private static $l;
    /**
     * @var \Akari\system\console\ConsoleOutput
     */
    public static $h;

	public static function getInstance($opt){
		if (self::$l == null) {
			self::$l = new self();
            self::$h = new ConsoleOutput();
		}
		return self::$l;
	}

	public function append($msg, $level) {
        $level = strtolower($level);
        self::$h->write("<$level>" .date('[Y-m-d H:i:s] ') . $msg. "</$level>");
		//fwrite(STDOUT, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL);
	}
}