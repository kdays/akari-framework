<?php
!defined("AKARI_PATH") && exit;

Class csrfModule{
	protected static $m;
	public static function getInstance(){
		if(!isset(self::$m)){
			self::$m = new self();
		}
		return self::$m;
	}

	public function run($p){
		$tokenName = Context::$appConfig->csrfTokenName;
		$token = Security::getCSRFToken();
		echo "<input type=\"hidden\" name=\"$tokenName\" value=\"$token\" />\n";
	}
}