<?php
namespace Akari\config;

use Akari\Context;

!defined("AKARI_PATH") && exit;

Class BaseConfig{
	public $appName = "Akari";
	public $appBaseURL = "http://localhost/";
	public $appVersion = "1.0";

	public $database = Array();
	public $cache = Array(
		"file" => Array(
            'default' => Array()
        ),
		"memcache" => Array(
            'default' => Array()
        ),
		'memcached' => Array(
            'default' => Array()
        )
	);
	public $defaultCacheType = "file";
	public $logs = Array(
		Array(
			'level' => AKARI_LOG_LEVEL_PRODUCTION,
			'appender' => 'Akari\system\log\FileLogger',
			'params' => Array('filename' => 'data/log/all.log')
		),
		Array(
			'level' => AKARI_LOG_LEVEL_ALL,
			'appender' => 'Akari\system\log\StandardOutputLogger',
			'enabled' => CLI_MODE
		)
	);

	public $defaultExceptionHandler = 'Akari\system\exception\DefaultExceptionHandler';
	public $defaultConsoleExceptionHandler = 'Akari\system\exception\DefaultConsoleExceptionHandler';
	public $csrfProtect = true;
	public $csrfTokenName = "_akari";

	public $charset = "utf-8";
	public $language = "cn";

	public $uriMode = AKARI_URI_AUTO;
	public $uriSuffix = "";
	public $uriDefault = "";

	public $templateSuffix = false;
	public $templateCache = "/data/tpl_cache";

	public $encryptionKey = 'Akaza Akari, Akkarin';
    public $encryptCipher = 'Akari\system\security\Cipher\AESCipher';
	public $cipherIv = "";
	public $cipherRSA = [
		"public_key" => "",
		"private_key" => ""
	];

	public $cookiePrefix = "kd_";
	public $cookieTime = "1 day";
	public $cookiePath = "/";
	public $cookieDomain = "";
	public $cookieSecure = false;
	public $cookieEncrypt= 'Akari\system\security\Cipher\AESCipher';

	public $triggerRule = Array(
		"pre" => Array(
		//	Array('/KK/', 'a')
		)
	);

	public $URLRewrite = Array(
		//"/kkd\/(.+)/" => 'manager/test'
	);

	public $uploadDir = '/static/attachment';
	public $allowUploadExt = Array("jpg", "gif", "png");

	public function getDBConfig($name = "default"){
		if(!is_array(current($this->database)))	return $this->database;
		if($name == "default")	return current($this->database);
		return $this->database[$name];
	}

    /**
     * 如果获得的配置不默认设置中，就检查是否在config目录下存在同名配置
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key) {
        // 如果没有key则进行检查
        if (!isset($this->$key)) {
            $baseConfDir = implode(DIRECTORY_SEPARATOR, Array(
                Context::$appBasePath, "app", "config"
            ));

            $baseFileArr = [
                Context::$mode. DIRECTORY_SEPARATOR. $key,
                Context::$mode.".".$key,
                $key
            ];

            foreach ($baseFileArr as $fileName) {
                $optPath = $baseConfDir. DIRECTORY_SEPARATOR. $fileName. ".php";
                if (file_exists($optPath)) {
                    $this->$key = require($optPath);
                    break;
                }

                $yamlPath = $baseConfDir. DIRECTORY_SEPARATOR. $fileName. ".yaml";
                if (file_exists($yamlPath)) {
                    $this->$key = \Spyc::YAMLLoad($yamlPath);
                    break;
                }

                $ymlPath = $baseConfDir. DIRECTORY_SEPARATOR. $fileName. ".yml";
                if (file_exists($ymlPath)) {
                    $this->$key = \Spyc::YAMLLoad($ymlPath);
                    break;
                }
            }
        }

        return isset($this->$key) ? $this->$key : NULL;
    }

	public static $c;
	public static function getInstance(){
		$h = get_called_class();
		if (!self::$c){
			self::$c = new $h;

            if (method_exists(self::$c, 'rewriteConfig')) {
                self::$c->rewriteConfig();
            }
		}

		return self::$c;
	}
}