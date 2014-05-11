<?php
/**
 * Akari Framework 2.0
 *
 */

function_exists('date_default_timezone_set') && date_default_timezone_set('Etc/GMT+0');
define("AKARI_VERSION", "2014.05.02");
define("AKARI_PATH", dirname(__FILE__).'/'); //兼容老版用
define("TIMESTAMP", time());

include("define.php");

Class Context{
	public static $nsPaths = Array();
	public static $classes = Array();

	public static $uri = null;
	public static $appEntryName = null;
	public static $appConfig = null;
	public static $appBasePath = null;
	public static $appEntryPath = null;

	public static $mode = FALSE;

	public static function register($nsName, $nsPath){
		self::$nsPaths[$nsName] = $nsPath;
	}

	public static function autoload($cls){
		if(isset(self::$classes[$cls]))	return ;

		$clsPath = false;
		if(isset(self::$nsPaths[$cls])){
			$clsPath = Context::$appBasePath. self::$nsPaths[$cls] .".php";
		}

		if($clsPath && file_exists($clsPath)){
			self::$classes[$cls] = true;
			require $clsPath;
		}else{
			$clsPath = Context::$appBasePath."/app/lib/$cls.php";
			if(file_exists( $clsPath )){
				self::$classes[$cls] = true;
				
				require($clsPath);
				return ;
			}
		}

		if(!$clsPath)	trigger_error("Not Found CLASS [ $cls ]", E_USER_ERROR);
	}

	public function getResourcePath($path){
		return realpath(Context::$appBasePath.$path);
	}
}
spl_autoload_register(Array('Context', 'autoload'));

Class akari{
	private static $f;
	public static function getInstance(){
		if (self::$f == null) {
            self::$f = new self();
        }
        return self::$f;
	}

	/**
	 * 框架引导用
	 **/
	public function initApp($appBasePath){
		include("config/BaseConfig.php");

		$confCls = "Config";
		if(file_exists(AKARI_PATH."dev.lock")){
			$confCls = "devConfig";
			Context::$mode = "dev";
		}	

		Context::$appBasePath = $appBasePath;
		include($appBasePath."/app/config/$confCls.php");
		$config = new $confCls();

		Context::$appConfig = $config;
		Context::$appEntryName = basename($_SERVER['SCRIPT_FILENAME']);

		$this->loadBase();
		$this->setExceptionHandler();

		return $this;
	}

	public function setExceptionHandler(){
		if(isset(Context::$appConfig->defaultExceptionHandler)){
			ExceptionProcessor::getInstance()->setHandler(Context::$appConfig->defaultExceptionHandler);
		}
	}

	public function run($uri = NULL, $outputBuffer = true, $params = ""){
		$config = Context::$appConfig;

		Logging::_log("App Start");
		if(!$uri){
			$router = Router::getInstance();
			$uri = $router->resolveURI();
		}
		Context::$uri = $uri;

		if($outputBuffer)	ob_start();

		$dispatcher = Dispatcher::getInstance();
		if(CLI_MODE){
			$clsPath = $dispatcher->invokeTask($uri);
		}else{
			$clsPath = $dispatcher->invoke($uri);
		}

		if($clsPath){
			Context::$appEntryPath = str_replace(Context::$appBasePath, '', $clsPath);

			HookRules::getInstance()->commitPreRule();
			require $clsPath;
			HookRules::getInstance()->commitAfterRule();
		}else{
			if(Context::$mode == FALSE){
				Header('HTTP/1.0 404 Not Found');
    			echo file_get_contents(AKARI_PATH."template/404.htm");
    			$this->stop();
			}

			Logging::_logInfo("not found $uri");
			throw new Exception("URI ERROR: $uri");
		}

		return $this;
	}

	/**
	 * 将基础数据载入
	 *
	 **/
	public function loadBase(){
		$lib = Array(
			"I18n" => "utility/I18n",
			"Auth" => "utility/Auth",
			"Pages" => "utility/Pages",
			"ImageThumb" => "utility/ImageThumb",
			"UploadHelper" => "utility/UploadHelper",
			"TemplateHelper" => "utility/TemplateHelper",

			"ExceptionProcessor" => "system/exception/ExceptionProcessor",
			"DefaultExceptionHandler" => "system/exception/DefaultExceptionHandler",

			"DBAgent" => "system/db/DBAgent",
			"DBParser" => "system/db/DBParser",
			"DBAgentFactory" => "system/db/DBAgentFactory",
			"DBAgentStatement" => "system/db/DBAgentStatement",

			"Dispatcher" => "system/http/Dispatcher",
			"HookRules" => "system/HookRules",
			"Router" => "system/http/Router",
			"Request" => "system/http/Request",
			"Cookie" => "system/http/Cookie",
			"Session" => "system/http/Session",

			"Logging" => "system/log/Logging",
			"FileLogger" => "system/log/FileLogger",
			"STDOutputLogger" => "system/log/STDOutputLogger",

			"Security" => "system/security/Security",
			"Cipher" => "system/security/Cipher/Cipher",
			"AESCipher" => "system/security/Cipher/AESCipher",
			"RawCipher" => "system/security/Cipher/RawCipher",
			"Base64Cipher" => "system/security/Cipher/Base64Cipher",
			"PwCipher" => "system/security/Cipher/PwCipher",

			"BaseCacheAdapter" => "system/data/BaseCacheAdapter",
			"FileAdapter" => "system/data/FileAdapter",
			"MemcacheAdapter" => "system/data/MemcacheAdapter",

			"Model" => "model/Model",
			"RequestModel" => "model/RequestModel",
			"DatabaseModel" => "model/DatabaseModel"
		);

		foreach($lib as $key => $value){
			Context::register($key, "/core/$value");
		}
		include("system/functions.php");
	}

	public function stop($code = 0, $msg = '') {
        if (!empty($msg)) {
            Logging::_logInfo('End the response. msg: ' . $msg);            
        } else {
            Logging::_logInfo('End the response.');
        }
        exit($code);
    }

	public function __destruct() {
        Logging::_log('Request ' . Context::$appConfig->appName . ' processed, total time: ' . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) . ' secs' );
    }
}