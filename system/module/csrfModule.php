<?php
namespace Akari\system\module;

use Akari\Context;
use Akari\system\security\Security;

!defined("AKARI_PATH") && exit;

Class CsrfModule{
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