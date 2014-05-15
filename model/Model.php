<?php
!defined("AKARI_PATH") && exit;

Class Model{
	public function __log($msg){
		Logging::_log($msg);
	}

	public function __logErr($msg){
		Logging::_logErr($msg);
	}
}