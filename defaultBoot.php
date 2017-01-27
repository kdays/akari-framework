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

$di->setShared('request', \Akari\system\http\Request::class);
$di->setShared('response', \Akari\system\http\Response::class);

$di->setShared('session', \Akari\system\http\Session::class);
$di->setShared('cookies', \Akari\system\http\Cookie::class);
$di->setShared('cookieEncrypt', \Akari\system\security\DefaultCookieEncrypt::class);

$di->setShared('view', Akari\system\tpl\View::class);
$di->setShared("dispatcher", Akari\system\router\Dispatcher::class);
$di->setShared("router", Akari\system\router\Router::class);
$di->setShared("processor", Akari\system\result\Processor::class);

$di->setShared('assets', Akari\system\tpl\asset\AssetsMgr::class);

$di->setShared("url", \Akari\system\router\BaseUrlGenerator::class);
$di->setShared("csrf", \Akari\system\http\VerifyCsrfToken::class);
$di->setShared("lang", \Akari\system\i18n\I18n::class);