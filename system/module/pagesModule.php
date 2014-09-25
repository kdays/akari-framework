<?php
namespace Akari\system\module;

use Akari\utility\Pages;

!defined("AKARI_PATH") && exit;

Class PagesModule{
	protected static $m;
	public static function getInstance(){
		if(!isset(self::$m)){
			self::$m = new self();
		}
		return self::$m;
	}

	public function run($p){
		$result = Pages::getInstance()->getHTML();
		echo $result;
	}
}