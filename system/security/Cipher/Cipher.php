<?php
namespace Akari\system\security\Cipher;

use Akari\Context;

abstract class Cipher{
	public static $instances = [];
	public $pMode = 'default';

	public static function _instance($mode) {
		$instanceName = get_called_class(). "_". $mode;
		if (!isset(self::$instances[ $instanceName ])) {
			$cls = get_called_class();
			self::$instances[ $instanceName ] = new $cls( $mode );
			self::$instances[ $instanceName ]->pMode = $mode;
		}

		return self::$instances[ $instanceName ];
	}

   	abstract public function encrypt($str);
	abstract  public function decrypt($str);
} 