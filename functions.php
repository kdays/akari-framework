<?php

/**
 * @param string $name
 * @param array $L
 * @return string
 * @throws \Akari\exception\AkariException
 */
function L(string $name, array $L = [], string $prefix = '') {
    $di = \Akari\system\ioc\DI::getDefault();

    return $di->getShared('lang')->get($name, $L, $prefix);
}


/**
 * @return \Akari\system\http\Request
 * @throws \Akari\exception\AkariException
 */
function request() {
    $di = \Akari\system\ioc\DI::getDefault();

    return $di->getShared('request');
}

/**
 * @return \Akari\system\router\Router
 * @throws \Akari\exception\AkariException
 */
function router() {
    $di = \Akari\system\ioc\DI::getDefault();

    return $di->getShared('router');
}

