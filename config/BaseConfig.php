<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:30
 */

namespace Akari\config;

use Akari\Context;
use Akari\exception\ExceptionProcessor;

Class BaseConfig {

    public $appName;
    public $appBaseURL;
    public $defaultURI = 'index';

    //
    public $notFoundTemplate = "";
    public $serverErrorTemplate = "";

    // 如果没有result时的callback
    public $nonResultCallback = NULL;

    public $logs = [
        [
            'level' => AKARI_LOG_LEVEL_PRODUCTION,
            'appender' => 'Akari\system\logger\FileLogger',
            'params' => [
                'filename' => 'runtime/log/all.log'
            ]
        ]
    ];

    public $cache = [
        'default' => 'file',

        'file' => [
            'default' => [
                /*'path' => '',
                'index' => '',
                'prefix' => ''*/
            ]
        ]
    ];

    public $database = [];

    public $defaultExceptionHandler = 'Akari\system\exception\DefaultExceptionHandler';
    public $defaultPageTemplate = 'Pager';

    public $uriMode = AKARI_URI_AUTO;
    public $uriSuffix = '';

    public $templateSuffix = ".htm";
    public $templateCacheDir = '/runtime/tpl';

    //public $defaultEncryptCipher = '\Akari\system\security\Cipher\AESCipher';
    public $encrypt = [
        'default' => [
            'cipher' => 'AES',
            'iv' => '',
            'key' => 'Akaza Akari, Akkarin'
        ],

        'cookie' => [
            'cipher' => 'AES',
            'iv' => '',
            'key' => 'Akari Framework v3'
        ]
    ];

    public $cookiePrefix = '';
    public $cookieTime = '';
    public $cookiePath = '';
    public $cookieSecure = false;
    public $cookieDomain = '';
    public $csrfTokenName = '_akari';

    public $uriEvent = [
        'pre' => [],
        'post' => []
    ];

    public $uriRewrite = [];

    public $mail = [
        'Username' => '',
        'Password' => '',
        'Host' => ''
    ];

    public $uploadDir = 'web/attachment/';
    public $allowUploadExt = [];

    public function getDBConfig($name = "default"){
        if(!is_array(current($this->database)))	return $this->database;
        if($name == "default")	return current($this->database);
        return $this->database[$name];
    }

    /**
     * @var string $key
     * @return null
     */
    public function loadExternalConfig($key) {
        $namePolicies = [
            Context::$mode. DIRECTORY_SEPARATOR. $key,
            Context::$mode. ".". $key,
            $key
        ];
        $baseConfig = Context::$appEntryPath. DIRECTORY_SEPARATOR. "config". DIRECTORY_SEPARATOR;

        foreach ($namePolicies as $name) {
            if (file_exists($baseConfig. $name. ".php")) {
                return include($baseConfig. $name. ".php");
            }

            if (file_exists($baseConfig. $name. ".yml")) {
                return \Spyc::YAMLLoad($baseConfig. $name. ".yml");
            }

        }
        return NULL;
    }

    public function __get($key) {
        if (!isset($this->$key)) {
            $this->$key = $this->loadExternalConfig($key);
        }

        return isset($this->$key) ? $this->$key : NULL;
    }

    public static $c;
    public static function getInstance(){
        $h = get_called_class();
        if (!self::$c){
            self::$c = new $h();
        }

        return self::$c;
    }
}