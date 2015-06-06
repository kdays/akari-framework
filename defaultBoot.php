<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午10:10
 */

$di = \Akari\system\ioc\DI::getDefault();

$di->setShared('viewEngine', '\Akari\system\tpl\engine\DefaultTemplateEngine');