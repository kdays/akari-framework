<?php
/**
 * Akari Framework 2
 *
 * @website http://kdays.cn/
 */

namespace Akari;

function_exists('date_default_timezone_set') && date_default_timezone_set('Etc/GMT+0');
define("AKARI_VERSION", "2.9 (Rhapsody)");
define("AKARI_BUILD", "2014.11.9");
define("AKARI_PATH", dirname(__FILE__).'/'); //兼容老版用
define("TIMESTAMP", time());
define("NAMESPACE_SEPARATOR", "\\");

include("define.php");
include("system/functions.php");

if (!DEBUG_MODE && function_exists("xdebug_disable")) {
	xdebug_disable();
}

Class Context{
	public static $nsPaths = Array('Akari' => AKARI_PATH);
    public static $aliases = Array();
	public static $classes = Array();

	public static $uri = null;
    public static $innerURI = null;
    public static $appBaseNS = "";
	public static $appEntryName = null;
    /**
     * @var object
     */
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
     *
     * @param string $cls 类名
     * @throws \Exception
     */
	public static function autoload($cls){
        $clsPath = false;
		if(isset(self::$aliases[$cls])){
			$cls = self::$aliases[$cls];
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
            require_once $clsPath;
		}else{
			$dif = array("lib", "model", "exception");

			foreach($dif as $dir){
				$clsPath = Context::$appBasePath.DIRECTORY_SEPARATOR. BASE_APP_DIR.
                    DIRECTORY_SEPARATOR.$dir.DIRECTORY_SEPARATOR.$cls.".php";

				if(file_exists( $clsPath )){
					self::$classes[$clsPath] = true;
					
					require_once $clsPath;
					return ;
				}
			}

			$clsPath = false;
		}
		
		if(!$clsPath){
            throw new \Exception("Not Found Class [ $cls ]", E_USER_ERROR);
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

use Akari\system\result\ResultProcessor;
use Akari\system\ResultHelper;
use Akari\system\TriggerRule;
use Akari\system\log\Logging;
use Akari\system\http\Dispatcher;
use Akari\system\http\Router;
use Akari\system\http\HttpStatus;
use Akari\system\Event;
use Akari\system\exception\ExceptionProcessor;
use Akari\utility\BenchmarkHelper;

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

	public static function getVersion($dispCodeName = true) {
		$version = AKARI_VERSION;

		return $dispCodeName ? $version : preg_replace('/\(\w+\)/', "", $version);
	}

    /**
     * 初始化框架，载入设定和组件，并设定错误处理器
     *
     * @param string $appBasePath 应用基础目录
     * @param string $appNS 应用命名空间
     * @return akari
     */
	public function initApp($appBasePath, $appNS){
        // 如果什么都没有
        $appDir = $appBasePath. DIRECTORY_SEPARATOR. BASE_APP_DIR. DIRECTORY_SEPARATOR;
        if (!is_dir($appDir)) {
            exit("Cannot found application directory, Please check framework define or upload");
        }

        BenchmarkHelper::setTimer("init:start");

		$confCls = "Config";
        Context::$mode = $this->getMode();
        if (!empty(Context::$mode)) {
            $confCls = Context::$mode . "Config";
        }

		Context::$appBasePath = $appBasePath;
		if(!file_exists($appDir."/config/$confCls.php")){
			trigger_error("not found config file [ $confCls ]", E_USER_ERROR);
		}

        Context::$appBaseNS = $appNS;

        Context::$nsPaths['core'] = __DIR__;
        Context::$nsPaths[ $appNS ] = $appDir;

        $confCls = $appNS.NAMESPACE_SEPARATOR."config".
            NAMESPACE_SEPARATOR.$confCls;

		if(!class_exists($confCls)){
			trigger_error("not found config class [ $confCls ]", E_USER_ERROR);
		}

        /**
         * @var $confCls \Akari\config\BaseConfig
         */
        Context::$appConfig = $confCls::getInstance();
		Context::$appEntryName = basename($_SERVER['SCRIPT_FILENAME']);

		Header("X-Framework: Akari Framework ". AKARI_BUILD);
		$this->loadExternal();
		$this->setExceptionHandler();
		ResultProcessor::getInstance()->setDefaultResultHandler('\Akari\system\result\DefaultResult');

		return $this;
	}
	
	/**
	 * 绑定事件错误器
	 * @return void
	 */
	public function setExceptionHandler(){
		$config = Context::$appConfig;

		if (CLI_MODE && isset($config->defaultConsoleExceptionHandler)) {
			ExceptionProcessor::getInstance()->setHandler($config->defaultConsoleExceptionHandler);
		} elseif (!CLI_MODE && isset($config->defaultExceptionHandler)) {
			ExceptionProcessor::getInstance()->setHandler($config->defaultExceptionHandler);
		}
	}
	
	/**
	 * 执行请求
	 * 
	 * @param string $uri URI地址
	 * @param boolean $outputBuffer 是否启用输出缓存
	 * @throws \Exception
	 * @return akari
	 */
	public function run($uri = NULL, $outputBuffer = true){
		$config = Context::$appConfig;

		if (CLI_MODE && function_exists("cli_set_process_title")) {
			cli_set_process_title("Akari PHP : ".$uri);
		}

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
			$config->appBaseURL
		);

        $clsPath = CLI_MODE ?
            $dispatcher->invokeTask($uri) :
            $dispatcher->invoke($uri);

        BenchmarkHelper::setTimer("init:end");

		if($clsPath){
			Context::$appEntryPath = str_replace(Context::$appBasePath, '', $clsPath);
			if (!CLI_MODE) {
				TriggerRule::getInstance()->commitPreRule();
			}

			// 如有特定某些触发器时 使用这个可以更精确的处理
			$_doAction = str_replace(Array(Context::$appBasePath, BASE_APP_DIR.'/action/', '.php'), '',
				$clsPath);
			Event::fire("action.".str_replace("/", ".", $_doAction));

            BenchmarkHelper::setTimer("app:start");

			/*$result = require($clsPath);

			if (is_a($result, '\Akari\system\result\Result')) {
				$result->doProcess();
			}
			*/
			require($clsPath);
            BenchmarkHelper::setTimer("app:end");
			if (!CLI_MODE)  TriggerRule::getInstance()->commitAfterRule();
		}else{
            if (!CLI_MODE) {
                HttpStatus::setStatus(HttpStatus::NOT_FOUND);
                include(AKARI_PATH."template/404.htm");
            }

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

    public function getMode() {
        static $mode = FALSE;

        if ($mode === FALSE) {
            $lock = glob(AKARI_PATH . "*.lock");
            $mode = NULL;

            if (isset($lock[0])) {
                $mode = ucfirst(basename($lock[0], ".lock"));
            }
        }

        return $mode;
    }

	public function __destruct() {
		Logging::_log('Request ' . Context::$appConfig->appName . ' processed, total time: ' . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) . ' secs' );
	}

	/**
	 * 用来处理一些框架必须的第三方组件载入
	 *
	 **/
	public function loadExternal() {
		$libList = require("external/classes.php");
		foreach ($libList as $nowLibName) {
			import($nowLibName);
		}

		$vendorPath = [
			Context::$appBasePath . DIRECTORY_SEPARATOR
			. BASE_APP_DIR . 'lib' . DIRECTORY_SEPARATOR
			. 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
			Context::$appBasePath . DIRECTORY_SEPARATOR
			. 'external' . DIRECTORY_SEPARATOR
			. 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php'
		];

		foreach ($vendorPath as $value) {
			if (file_exists($value)) {
				require $value;
			}
		}

	}
}