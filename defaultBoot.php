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

$di->setShared('cookieEncrypt', function() {
    return new \Akari\system\security\CookieEncryptHelper();
});
