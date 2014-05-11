<?php
!defined("AKARI_PATH") && exit;

Class STDOutputLogger{
	private static $l;

	public static function getInstance($opt){
		if (self::$l == null) {
            self::$l = new self();
        }
        return self::$l;
	}

	public function append($msg) {
		fwrite(STDOUT, date('[Y-m-d H:i:s] ') . $msg . PHP_EOL);
	}
}