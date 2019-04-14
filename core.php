<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:12
 */

namespace Akari;

use Akari\exception\PHPFatalException;
use Akari\system\BaseConfig;
use Akari\system\event\Event;
use Akari\system\ioc\Injectable;
use Akari\system\result\Result;
use Akari\system\router\Dispatcher;
use Akari\system\util\ExceptionUtil;

class Core extends Injectable {

    protected static $fkInstance = NULL;

    /**
     * @var BaseConfig
     */
    protected static $appConfig;
    public static $appNs;
    public static $appDir;
    public static $baseDir;

    public static function env(string $key, $defaultValue = NULL) {
        return self::$appConfig->$key ?? $defaultValue;
    }

    public static function initApp(string $baseDir, string $appNs, BaseConfig $config = NULL, string $appEntryFolderName = 'app') {
        if (!empty(self::$fkInstance)) {
            return self::$fkInstance;
        }

        $akari = new self();

        $appDir = $baseDir . DIRECTORY_SEPARATOR . $appEntryFolderName;
        Loader::register($appNs, $appDir);

        if ($config === NULL) {
            $configCls = implode(NAMESPACE_SEPARATOR, [$appNs, 'config', 'Config']);
            $config = new $configCls();
        }

        self::$baseDir = $baseDir;
        self::$appNs = $appNs;
        self::$appConfig = $config;
        self::$fkInstance = $akari;
        self::$appDir = Loader::getDir($appNs);

        if (file_exists($defBoot = AKARI_PATH . "/defaultBoot.php")) {
            include($defBoot);
        }

        $akari->registerException();

        return self::$fkInstance;
    }

    protected function registerException() {
        set_error_handler(function($code, $message, $file, $line, $context) {
            throw new \ErrorException($message, $code, $code, $file, $line);
        }, error_reporting());

        $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_USER_ERROR, E_COMPILE_ERROR];

        set_exception_handler([ExceptionUtil::class, 'dispatchException']);
        register_shutdown_function(function() use($fatalTypes) {
            $error = error_get_last();
            if (!empty($error) && in_array($error['type'], $fatalTypes)) {
                $ex = new PHPFatalException($error['message'], $error['file'], $error['line'], $error['type']);
                ExceptionUtil::dispatchException($ex);
            }
        });
    }

    public function run(?string $uri, $outputBuffer = TRUE) {
        $uri = $uri ?? $this->router->resolveURI();

        if ($outputBuffer)  ob_start();
        if (!CLI_MODE) {
            $uri = $this->router->getUrlFromRule($uri, NULL);
        } else {
            $this->dispatcher->setActionNameSuffix('Task');
        }

        $toParameters = $this->router->getParameters();
        $this->dispatcher->initFromUrl($uri, $toParameters);

        Event::fire(Dispatcher::EVENT_APP_START, []);
        $result = $this->dispatcher->dispatch();
        if ($result instanceof Result) {
            $this->processor->process($result);
        }
        Event::fire(Dispatcher::EVENT_APP_END, []);

        $this->response->send();
    }

}

