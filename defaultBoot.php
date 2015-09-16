<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午10:10
 */

use Akari\Context;
use Akari\system\logger\handler\FileLoggerHandler;

$di = \Akari\system\ioc\DI::getDefault();

$di->setShared('viewEngine', '\Akari\system\tpl\engine\MinatoTemplateEngine');

$di->setShared('logger', function() {
    $logger = new FileLoggerHandler();
    $logger->setLevel(AKARI_LOG_LEVEL_PRODUCTION);
    
    $logPath = Context::$appBasePath. DIRECTORY_SEPARATOR. "runtime/log/default.log";
    $logger->setOption("path", $logPath);
    
    return $logger;
});