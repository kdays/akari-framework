<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午10:10
 */

use Akari\Context;

$di = \Akari\system\ioc\DI::getDefault();

$di->setShared('viewEngine', \Akari\system\tpl\engine\MinatoTemplateEngine::class);

$di->setShared('logger', function() use($di) {
    $logger = new Akari\system\logger\handler\FileLoggerHandler();
    $logger->setLevel(AKARI_LOG_LEVEL_PRODUCTION);
    
    $logPath = Context::$appBasePath. DIRECTORY_SEPARATOR. "runtime/log/default.log";
    $logger->setOption("path", $logPath);
    
    return $logger;
});

if (!CLI_MODE) {
    $di->setShared('request', \Akari\system\http\Request::class);
    $di->setShared('response', \Akari\system\http\Response::class);
} else {
    $di->setShared('request', \Akari\system\console\Request::class);
    $di->setShared('response', \Akari\system\console\Response::class);
}

$di->setShared("dispatcher", Akari\system\router\Dispatcher::class);
$di->setShared("router", Akari\system\router\Router::class);
$di->setShared("processor", Akari\system\result\Processor::class);

$di->setShared('cookieEncrypt', \Akari\system\security\CookieEncryptHelper::class);
