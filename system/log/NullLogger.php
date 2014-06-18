<?php
Class NullLogger{
	private static $l;

	public static function getInstance($opt){
		if (self::$l == null) {
			self::$l = new self();
		}
		return self::$l;
	}

	public function append($msg) {
		
	}
}