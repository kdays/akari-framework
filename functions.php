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

function env(string $key, $defaultValue = NULL) {
    return \Akari\Core::env($key, $defaultValue);
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

/**
 * @param array $items
 * @return \Akari\system\util\Collection
 */
function collection($items) {
    if ($items instanceof \Akari\system\util\Collection) {
        return $items;
    }

    return new \Akari\system\util\Collection($items);
}

function e(string $message) {
    throw new \Akari\exception\AkariException($message);
}
