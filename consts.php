<?php
function_exists('date_default_timezone_set') && date_default_timezone_set('UTC');

define("AKARI_BUILD", "20190224");
define("AKARI_PATH", dirname(__FILE__) . '/'); //兼容老版用

define("TIMESTAMP", time());
define("NAMESPACE_SEPARATOR", "\\");
define("CLI_MODE", php_sapi_name() == "cli" ? TRUE : FALSE);
