<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:30
 */

namespace Akari\config;

use Akari\Context;
use Akari\system as Sys;

class BaseConfig {

    public $appName = "Application";
    public $appBaseURL;
    public $defaultURI = 'index/index';

    public $notFoundTemplate = "";
    public $serverErrorTemplate = "";

    public $timeZone = 8;

    // 如果没有result时的callback
    public $nonResultCallback = NULL;
    public $offsetTime = 0;

    /** @var bool|callable  */
    public $exceptionAutoLogging = TRUE;

    public $bindDomain = [
        'default' => '\action'
    ];

    public $cache = [
        'default' => [
            'handler' => Sys\cache\handler\FileCacheHandler::class,
            'baseDir' => '/runtime/cache/',
            'indexPath' => 'index.json'
        ],

        'redis' => [
            'handler' => Sys\cache\handler\RedisCacheHandler::class,
            'host' => '127.0.0.1'
        ]
    ];

    public $storage = [
        'default' => [
            'handler' => Sys\storage\handler\FileStorageHandler::class,
            'baseDir' => 'storage/'
        ]
    ];

    public $filters = [
        'default' => Sys\security\filter\DefaultFilter::class
    ];

    public $database = [];

    public $defaultExceptionHandler = Sys\exception\DefaultExceptionHandler::class;

    public $uriMode = AKARI_URI_AUTO;
    public $uriSuffix = '';

    public $templateNamePrefix = '';
    public $templateCacheDir = '/runtime/tpl';

    public $encrypt = [
        'default' => [
            'cipher' => Sys\security\cipher\AESCipher::class,
            'options' => [
                'secret' => 'Hello, Akari Framework'
            ]
        ],

        'cookie' => [
            'cipher' => Sys\security\cipher\AESCipher::class,
            'options' => [
                'secret' => 'Answer is 42.'
            ]
        ]
    ];

    public $cookiePrefix = 'w_';
    public $cookieTime = 86400;
    public $cookiePath = '/';
    public $cookieSecure = FALSE;
    public $cookieDomain = '';

    public $autoPostTokenCheck = TRUE; // 开启时,POST提交会自动检查令牌
    public $csrfTokenName = '_akari';

    public $trigger = [
        // URL路由分发前，可以对URL进行简单处理和标记
        'beforeDispatch' => [
            //['/\.json/', 'JSONSupport']
        ],

        // URL路由分发后，执行操作前，可以对权限之类进行检查
        'applicationStart' => [],

        // 执行操作后返回结果的处理，可以记录性能和对Result进行额外处理
        'applicationEnd' => [],

        // 结果已经输出到OutputBuffer,可以进行繁体化之类的操作
        'applicationOutput' => []
    ];

    public $uriRewrite = [];

    public $uploadDir = 'web/attachment/';
    public $allowUploadExt = [];

    public function getDBConfig($name = "default") {
        if(!is_array(current($this->database)))	return $this->database;
        if($name == "default")	return current($this->database);
        if (!isset($this->database[$name])) throw new \Exception("not found DB config: " . $name);
        return $this->database[$name];
    }

    /**
     * @var string $key
     * @return null
     */
    public final function loadExternalConfig($key) {
        $namePolicies = [
            Context::$mode . DIRECTORY_SEPARATOR . $key,
            Context::$mode . "." . $key,
            $key
        ];
        $baseConfig = Context::$appEntryPath . DIRECTORY_SEPARATOR . "config" . DIRECTORY_SEPARATOR;

        foreach ($namePolicies as $name) {
            if (file_exists($baseConfig . $name . ".php")) {
                return include $baseConfig . $name . ".php";
            }

            if (file_exists($baseConfig . $name . ".yml")) {
                return \Spyc::YAMLLoad($baseConfig . $name . ".yml");
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
    public static function getInstance() {
        $h = get_called_class();
        if (!self::$c){
            self::$c = new $h();
        }

        return self::$c;
    }
}
