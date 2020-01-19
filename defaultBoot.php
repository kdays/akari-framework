<?php

$di = \Akari\system\ioc\DI::getDefault();

$di->setShared('request', \Akari\system\http\Request::class);
$di->setShared('response', \Akari\system\http\Response::class);
$di->setShared('cookie', \Akari\system\http\Cookie::class);

$di->setShared('view', \Akari\system\view\View::class);
$di->setShared('dispatcher', \Akari\system\router\Dispatcher::class);
$di->setShared('router', \Akari\system\router\Router::class);
$di->setShared('url', \Akari\system\router\UrlGenerator::class);
$di->setShared('processor', \Akari\system\result\Processor::class);
$di->setShared('assets', \Akari\system\view\assets\AssetsManager::class);
$di->setShared("lang", \Akari\system\util\I18n::class);

if (CLI_MODE) {
    $di->setShared('input', \Akari\system\console\Input::class);
    $di->setShared('output', \Akari\system\console\Output::class);
}

/** @var \Akari\system\view\View $view */
$view = $di->getShared('view');
$view->registerEngine("phtml", function () {
    $engine = new \Akari\system\view\engine\RawViewEngine();

    return $engine;
});

/** @var \Akari\system\util\I18n $lang */
$lang = $di->getShared('lang');
$lang->register( require __DIR__ . "/lang/zh.php");
