<?php
/**
 * Akari Framework 3
 *
 *
 */

namespace Akari;

use Akari\system\event\Event;
use Akari\system\event\Listener;
use Akari\system\exception\ExceptionProcessor;
use Akari\system\event\Trigger;
use Akari\system\result\Processor;
use Akari\system\result\Result;
use Akari\system\router\Dispatcher;
use Akari\system\router\Router;
use Akari\system\router\URL;
use Akari\system\security\Security;
use Akari\utility\Benchmark;
use Akari\utility\helper\Logging;

function_exists('date_default_timezone_set') && date_default_timezone_set('Etc/GMT+0');
define("AKARI_PATH", dirname(__FILE__).'/'); //兼容老版用
define("TIMESTAMP", time());

include("const.php");
include("function.php");

Class Context {

    public static $aliases = [];

    public static $nsPaths = [
        'Akari' => AKARI_PATH
    ];

    /**
     * 当前已载入的类列表
     * 
     * @var array
     */
    public static $classes = [];

    /**
     * 当前运行的模式
     *
     * @var bool|string
     */
    public static $mode = FALSE;

    /**
     * 当前访问的地址
     * @var string
     */
    public static $uri;

    /**
     * APP实际运行的命名空间
     * @var string
     */
    public static $appBaseNS;

    /**
     * 该网站实际路径(即应用实际文件夹的上一层)
     * @var string
     */
    public static $appBasePath;

    /**
     * 用户访问时调用的文件夹
     * @var string
     */
    public static $appWebPath;

    /**
     * 应用实际文件夹
     * @var string
     */
    public static $appEntryPath;

    /**
     * 应用执行的文件位置
     *
     * @var string
     */
    public static $appExecute;

    /**
     * 应用唯一行为标示符
     * 
     * @var string
     */
    public static $appSpecAction;

    /**
     * 框架执行的入口文件名
     * 
     * @var string
     */
    public static $appEntryName;

    /**
     * 应用调用方法
     * 
     * @var NULL|string
     */
    public static $appEntryMethod = NULL;

    /**
     * 应用配置
     * 
     * @var \Akari\config\BaseConfig $appConfig
     */
    public static $appConfig;

    /**
     * 是否在测试模式
     * @var bool
     */
    public static $testing = FALSE;
    

    public static function autoload($cls) {
        $clsPath = false;
        if(isset(self::$aliases[$cls])){
            $cls = self::$aliases[$cls];
        }

        $nsPath = explode("\\", $cls);
        if ( isset(Context::$nsPaths[$nsPath[0]]) ) {
            $basePath = Context::$nsPaths[ array_shift($nsPath)];
            $clsPath = $basePath.DIRECTORY_SEPARATOR.implode(DIRECTORY_SEPARATOR, $nsPath).".php";
        }

        if ($clsPath) {
            $clsPath = realpath($clsPath);
        }

        if(isset(self::$classes[$clsPath]))	return ;

        if($clsPath && file_exists($clsPath)) {
            self::$classes[$clsPath] = true;
            require_once $clsPath;
        }

        if(!$clsPath){
            throw new NotFoundClass($cls);
        }
    }

    public static function env($key, $value = NULL, $defaultValue = NULL) {
        if ($value === NULL) {
            return Context::$appConfig->$key ?: $defaultValue;
        }

        Context::$appConfig->$key = $value;
    }
}
spl_autoload_register(Array('Akari\Context', 'autoload'));


Class akari {

    use Logging;

    private static $f;
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
     * @param string $appBasePath 应用所在的上级目录（即app的上级）
     * @param string $appNS 应用程序申请的命名空间
     * @param null $defaultConfig 默认设置
     * @param string $webPath Web当前访问没目录
     *
     * @return $this
     * @throws FrameworkInitFailed
     */
    public function initApp($appBasePath, $appNS, $defaultConfig = NULL, $webPath = NULL) {
        $appDir = implode(DIRECTORY_SEPARATOR, [$appBasePath, BASE_APP_DIR, ""]);
        if (!is_dir($appDir) && !Context::$testing) {
            printf("not found application directory [ %s ]", $appDir);
            die;
        }

        $appDir = realpath($appDir). DIRECTORY_SEPARATOR;

        Context::$appWebPath = $webPath===NULL ? $appBasePath : $webPath;
        Context::$appBasePath = $appBasePath;
        Context::$appBaseNS = $appNS;
        Context::$nsPaths[ $appNS ] = $appDir;
        Context::$appEntryPath = $appDir;

        if ($appNS == 'Akari' && Context::$testing) { // 测试时才会出现这个情况
            Context::$appBasePath = realpath($appBasePath. "/../");
            Context::$nsPaths[ $appNS ] = $appBasePath;
            Context::$appEntryPath = $appBasePath;
        }

        Context::$mode = $this->getMode();
        $confClsName = (empty(Context::$mode) ? "" : Context::$mode). "Config";

        if ($defaultConfig == NULL) {
            if (!file_exists( $appDir. "config/$confClsName.php" )) {
                throw new FrameworkInitFailed( sprintf("config %s not found", $confClsName) );
            }

            /**
             * @var \Akari\config\BaseConfig $confCls
             */
            $confCls = $appNS. NAMESPACE_SEPARATOR. "config". NAMESPACE_SEPARATOR. $confClsName;
        } else {
            $confCls =  $defaultConfig;
        }

        Context::$appConfig = $confCls::getInstance();
        // 应该是实际执行的Action被赋值
        //Context::$appEntryName = basename($_SERVER['SCRIPT_FILENAME']);

        Header("X-Akari-Version: ". self::getVersion(false));
        include("defaultBoot.php");
        if (file_exists(Context::$appBasePath . "boot.php")) {
            include(Context::$appBasePath . "boot.php");
        }

        $this->loadExternal();
        if (!Context::$testing) $this->setExceptionHandler();

        return $this;
    }

    public function setExceptionHandler() {
        ExceptionProcessor::getInstance()->setHandler(Context::$appConfig->defaultExceptionHandler);

        // 异常发生时Event发射到Logging方法
        Listener::add(ExceptionProcessor::EVENT_EXCEPTION_EXECUTE, function(Event $event) {
            /** @var \Exception $ex */
            $ex = $event->params;

            $level = AKARI_LOG_LEVEL_ERROR;
            if (isset($ex->logLevel)) {
                $level = $ex->logLevel;
            }

            $this->_log(
                sprintf("Message: %s File: %s",
                    $ex->getMessage(),
                    str_replace(Context::$appBasePath, '', $ex->getFile()). ":" .$ex->getLine()),
                $level
            );
        });
    }

    public function run($uri = NULL, $outputBuffer = TRUE) {
        $config = Context::$appConfig;
        Benchmark::setTimer('app.start');

        $router = Router::getInstance();
        if (!$uri) {
            $uri = $router->resolveURI();

            Context::$appConfig->appBaseURL = $router->rewriteBaseURL($config->appBaseURL);
        }

        Context::$uri = $uri;

        if ($outputBuffer)  ob_start();
        Trigger::initEvent();

        $result = Trigger::handle('beforeDispatch');
        if ($result === NULL) {
            if (!CLI_MODE) {
                Dispatcher::getInstance()->appInvoke( $router->getRewriteURL(Context::$uri) );
            }

            $result = Trigger::handle('applicationStart', $result);
        }

        // 如果没有result 说明触发都表示没啥可吐槽的
        if (!isset($result)) {
            Security::autoVerifyCSRFToken(); // Token检查
            
            $dispatcher = Dispatcher::getInstance();
            $realResult = CLI_MODE ?
                $dispatcher->dispatchTask($uri) :
                $dispatcher->dispatch();

            if (!is_a($realResult, \Akari\system\result\Result::class)) {
                $defaultCallback = $config->nonResultCallback;

                if (is_callable($defaultCallback)) {
                    $realResult = $defaultCallback($realResult);
                } else {
                    $realResult = new Result(Result::TYPE_HTML, $realResult, NULL);
                }
            }
            
            $result = Trigger::handle('applicationEnd', $realResult);
        }

        // 下面是结果 如果有result 直接处理result
        $processor = Processor::getInstance();
        Benchmark::logParams('app.time', ['time' => Benchmark::getTimerDiff('app.start')]);
        
        if (isset($result)) {
            $processor->processResult($result);
        } elseif (isset($realResult)) {
            // 如果不是result 则会调用设置nonResultCallback来处理result 如果没有设置则按照HTML返回
            $processor->processResult($realResult);
        }
    }

    public function getMode() {
        static $mode = FALSE;

        if ($mode === FALSE) {
            $lock = glob(Context::$appBasePath . "*.lock");
            $mode = NULL;

            if (isset($lock[0])) {
                $mode = ucfirst(basename($lock[0], ".lock"));
            }
            
            $cfgMode = get_cfg_var("akari.MODE");
            if (!empty($cfgMode)) {
                $mode = ucfirst($cfgMode);
            }
        }

        return $mode;
    }

    public function stop($msg = '', $code = 0) {
        if (!empty($msg)) {
            self::_logInfo('End the response. msg: ' . $msg);
        } else {
            self::_logInfo('End the response.');
        }
        exit($code);
    }

    public function __destruct() {
        if (!CLI_MODE && DISPLAY_BENCHMARK) {
            include("template/BenchmarkResult.php");
        }

        self::_logInfo('Request ' . Context::$appConfig->appName .
            ' processed, total time: ' . (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) .
            ' secs' );
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
            Context::$appEntryPath . 'lib' . DIRECTORY_SEPARATOR
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

    public function register(URL $event) {
        Context::$appConfig->uriRewrite[ $event->makeRegExp() ] = $event->getCallback();
    }
}

Class FrameworkInitFailed extends \Exception {


}

Class NotFoundClass extends \Exception {

    public $className;

    public function __construct($clsName) {
        $this->className = $clsName;
        $this->message = "not found class [ ". $clsName. " ] ";
    }

}