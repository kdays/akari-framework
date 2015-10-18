<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 17:01
 */

namespace Akari\system\security\cipher;

use Akari\Context;

abstract Class Cipher implements ICipher {

    public $pMode = 'default';
    protected static $instance = [];

    protected static $d = NULL;
    protected static function _instance($mode) {
        $instanceName = get_called_class(). "_". $mode;
        if (!isset(self::$instance[ $instanceName ])) {
            $cls = get_called_class();
            self::$instance[ $instanceName ] = new $cls($mode);
            self::$instance[ $instanceName ]->pMode = $mode;
        }

        return self::$instance[ $instanceName ];
    }

    protected function getConfig($mode, $subKey = FALSE) {
        $config = Context::$appConfig->encrypt[$mode];
        return $subKey ? $config[$subKey] : $config;
    }

    abstract public function encrypt($text);
    abstract public function decrypt($text);

}


Class CipherException extends \Exception {

}