<?php
define("AKARI_BUILD", "20200119");
define("AKARI_PATH", dirname(__FILE__) . '/'); //兼容老版用

define("NAMESPACE_SEPARATOR", "\\");
define("CLI_MODE", php_sapi_name() == "cli" ? TRUE : FALSE);
