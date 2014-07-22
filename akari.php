<?php
/**
 * Akari Framework 2.0
 *
 */

namespace Akari;

function_exists('date_default_timezone_set') && date_default_timezone_set('Etc/GMT+0');
define("AKARI_VERSION", "2.2 (Largo)");
define("AKARI_BUILD", "2014.07.20");
define("AKARI_PATH", dirname(__FILE__).'/'); //兼容老版用
define("TIMESTAMP", time());

include("define.php");
include("system/functions.php");

Class Context{
	public static $nsPaths = Array('Akari' => AKARI_PATH);
    public static $aliases = Array();
	public static $classes = Array();

	public static $uri = null;
    public static $appBaseNS = "";
	public static $appEntryName = null;
	public static $appConfig = null;
	public static $appBasePath = null;
	public static $appEntryPath = null;
	public static $lastTemplate = null;

	public static $mode = FALSE;
	
	/**
	 * 注册类库载入
	 * @param string $nsName 名字
	 * @param string $nsPath 路径
	 */
	public static function register($nsName, $nsPath){
		self::$aliases[$nsName] = $nsPath;
	}
	
	/**
	 * 自动载入用方法
	 * @param string $cls 类名
	 */
	public static function autoload($cls){
        $clsPath = false;
		if(isset(self::$aliases[$cls])){
			$clsPath = Context::$appBasePath. self::$aliases[$cls] .".php";
		}

        // 处理字段 首先取第一个
        $nsPath = explode("\\", $cls);
        if ( isset(Context::$nsPaths[$nsPath[0]]) ) {
            $basePath = Context::$nsPaths[ array_shift($nsPath)];
            $clsPath = $basePath.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $nsPath).".php";
        }

        if ($clsPath) {
            $clsPath = realpath($clsPath);
        }

        if(isset(self::$classes[$clsPath]))	return ;

		if($clsPath && file_exists($clsPath)){
			self::$classes[$clsPath] = true;
			require $clsPath;
		}else{
			$dif = array("lib", "model", "exception");

			foreach($dif as $dir){
				$clsPath = Context::$appBasePath."/app/$dir/$cls.php";
				if(file_exists( $clsPath )){
					self::$classes[$cls] = true;
					
					require($clsPath);
					return ;
				}
			}

			$clsPath = false;
		}
		
		if(!$clsPath){
            throw new \Exception("Not Found CLASS [ $cls ]", E_USER_ERROR);
        }
	}

    /**
     * 获得资源路径
     *
     * @param string $path 路径
     * @param bool $toURL
     * @return string
     */
	public function getResourcePath($path, $toURL = false){
		if($toURL){
			return str_replace(Context::$appBasePath, '', $path);
		}
		return realpath(Context::$appBasePath.$path);
	}
}
spl_autoload_register(Array('Akari\Context', 'autoload'));

use Akari\system\triggerRule;
use Akari\system\log\Logging;
use Akari\system\http\Dispatcher;
use Akari\system\http\Router;
use Akari\system\http\HttpStatus;
use Akari\system\Event;
use Akari\system\exception\ExceptionProcessor;

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
	public function initApp($appBasePath, $appNS){
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

        Context::$appBaseNS = $appNS;
        Context::$nsPaths[ $appNS ] = $appBasePath."/app/";

		require($confPath);

        $confCls = $appNS."\\config\\".ucfirst($confCls);
		if(!class_exists($confCls)){
			trigger_error("Config Class Name [ $confCls ] Err", E_USER_ERROR);
		}

		Context::$appConfig = $confCls::getInstance();
		Context::$appEntryName = basename($_SERVER['SCRIPT_FILENAME']);

		Header("X-Framework: Akari Framework ". AKARI_BUILD);
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

		// rewrite baseURL
		Context::$appConfig->appBaseURL = $dispatcher->rewriteBaseURL(
			Context::$appConfig->appBaseURL
		);
		
		if(CLI_MODE){
			$clsPath = $dispatcher->invokeTask($uri);
		}else{
			$clsPath = $dispatcher->invoke($uri);
		}
		Context::$uri = $uri;

		if($clsPath){
			Context::$appEntryPath = str_replace(Context::$appBasePath, '', $clsPath);
			TriggerRule::getInstance()->commitPreRule();

			// 如有特定某些触发器时 使用这个可以更精确的处理
			$_doAction = str_replace(Array(Context::$appBasePath, '/app/action/', '.php'), '', 
				$clsPath);
			Event::fire("action.".str_replace("/", ".", $_doAction));

			require $clsPath;
			TriggerRule::getInstance()->commitAfterRule();
		}else{
			HttpStatus::setStatus(HttpStatus::NOT_FOUND);
			include(AKARI_PATH."template/404.htm");
			$this->stop();
		}

		return $this;
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