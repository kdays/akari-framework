<?php
!defined("AKARI_PATH") && exit;


Class BaseConfig{

	public $appName = "Akari";
	public $appBaseURL = "http://localhost/";
	public $appVersion = "1.0";

	public $database = Array();
	public $cache = Array(
		"file" => Array(),
		"memcache" => Array()
	);
	public $defaultCacheType = "file";
	public $logs = Array(
		Array(
			'level' => AKARI_LOG_LEVEL_PRODUCTION,
			'appender' => 'FileLogger',
			'params' => Array('filename' => 'data/log/all.log')
		),
		Array(
			'level' => AKARI_LOG_LEVEL_ALL,
			'appender' => 'STDOutputLogger',
			'enabled' => CLI_MODE,
			'url' => "/test/"
		)
	);

	public $defaultExceptionHandler = 'core/system/exception/DefaultExceptionHandler';
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
	public $encryptCipher = "AESCipher";
	public $cipherIv = "";

	public $cookiePrefix = "kd_";
	public $cookieTime = "1 day";
	public $cookiePath = "/";
	public $cookieDomain = "";
	public $cookieSecure = false;
	public $cookieEncrypt= "AESCipher";

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
}