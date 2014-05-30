<?php
/**
 * Akari Framework 2.0
 *
 */

function_exists('date_default_timezone_set') && date_default_timezone_set('Etc/GMT+0');
define("AKARI_VERSION", "2.0 (Largo)");
define("AKARI_BUILD", "2014.05.28");
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
	
	/**
	 * 注册类库载入
	 * @param string $nsName 名字
	 * @param string $nsPath 路径
	 */
	public static function register($nsName, $nsPath){
		self::$nsPaths[$nsName] = $nsPath;
	}
	
	/**
	 * 自动载入用方法
	 * @param string $cls 类名
	 */
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
			
			// 在测试到model中查找，这不是一个好的方法策略
			$clsPath = Context::$appBasePath."/app/model/$cls.php";
			if(file_exists( $clsPath )){
				self::$classes[$cls] = true;
				
				require($clsPath);
				return ;
			}

			$clsPath = false;
		}
		
		if(!$clsPath) trigger_error("Not Found CLASS [ $cls ]", E_USER_ERROR);
	}
	
	/**
	 * 获得资源路径
	 * @param string $path 路径
	 * @return string
	 */
	public function getResourcePath($path, $toURL = false){
		if($toURL){
			return str_replace(Context::$appBasePath, '', $path);
		}
		return realpath(Context::$appBasePath.$path);
	}
}
spl_autoload_register(Array('Context', 'autoload'));

Class akari{
	private static $f;
	/**
	 * 框架单例
	 * @return akari
	 */
	public static function getInstance(){
		if (self::$f == null) {
			self::$f = new self();
		}
		return self::$f;
	}

	/**
	 * 初始化框架，载入设定和组件，并设定错误处理器
	 * 
	 * @param string $appBasePath 应用基础目录
	 * @return akari
	 */
	public function initApp($appBasePath){
		include("config/BaseConfig.php");

		$confCls = "Config";
		
		$lock = glob(AKARI_PATH."*.lock");
		if(isset($lock[0])){
			Context::$mode = basename($lock[0], ".lock");
			$confCls = Context::$mode."Config";
		}
		
		Context::$appBasePath = $appBasePath;
		if(!file_exists($confPath = $appBasePath."/app/config/$confCls.php")){
			trigger_error("Not Found Mode Config [ $confCls ]", E_USER_ERROR);
		}
		
		include($confPath);
		if(!class_exists($confCls)){
			trigger_error("Config Class Name [ $confCls ] Err", E_USER_ERROR);
		}

		Context::$appConfig = new $confCls();
		Context::$appEntryName = basename($_SERVER['SCRIPT_FILENAME']);

		$this->loadBase();
		$this->setExceptionHandler();

		return $this;
	}
	
	/**
	 * 绑定事件错误器
	 * @return void
	 */
	public function setExceptionHandler(){
		if(isset(Context::$appConfig->defaultExceptionHandler)){
			ExceptionProcessor::getInstance()->setHandler(Context::$appConfig->defaultExceptionHandler);
		}
	}
	
	/**
	 * 执行请求
	 * 
	 * @param string $uri URI地址
	 * @param boolean $outputBuffer 是否启用输出缓存
	 * @param string $params CLI模式时传递用的参数
	 * @throws Exception
	 * @return akari
	 */
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
			if (function_exists("cli_set_process_title")) {
				cli_set_process_title($uri." - Akari Framework");
			}
		}else{
			$clsPath = $dispatcher->invoke($uri);
		}

		if($clsPath){
			Context::$appEntryPath = str_replace(Context::$appBasePath, '', $clsPath);

			TriggerRule::getInstance()->commitPreRule();
			require $clsPath;
			TriggerRule::getInstance()->commitAfterRule();
		}else{
			HttpStatus::setStatus(HttpStatus::NOT_FOUND);
			include(AKARI_PATH."template/404.htm");
			$this->stop();
		}

		return $this;
	}

	/**
	 * 将框架相关组件载入
	 * @return void
	 **/
	public function loadBase(){
		$lib = Array(
			"I18n" => "utility/I18n",
			"Auth" => "utility/Auth",
			"Pages" => "utility/Pages",
			"curl" => "utility/curl",
			"ImageThumb" => "utility/ImageThumb",
			"UploadHelper" => "utility/UploadHelper",
			"TemplateHelper" => "utility/TemplateHelper",
			"DataHelper" => "utility/DataHelper",
			"MessageHelper" => "utility/MessageHelper",

			"ExceptionProcessor" => "system/exception/ExceptionProcessor",
			"DefaultExceptionHandler" => "system/exception/DefaultExceptionHandler",

			"DBAgent" => "system/db/DBAgent",
			"DBParser" => "system/db/DBParser",
			"DBAgentFactory" => "system/db/DBAgentFactory",
			"DBAgentStatement" => "system/db/DBAgentStatement",

			"Dispatcher" => "system/http/Dispatcher",
			"TriggerRule" => "system/TriggerRule",
			"Event" => "system/Event",
			"Router" => "system/http/Router",
			"Request" => "system/http/Request",
			"Cookie" => "system/http/Cookie",
			"Session" => "system/http/Session",
			"MobileDevice" => "system/http/MobileDevice",
			"HttpStatus" => "system/http/HttpStatus",

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
			"CodeModel" => "model/CodeModel",
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