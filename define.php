<?php
define('AKARI_LOG_LEVEL_DEBUG', 0b00001);
define('AKARI_LOG_LEVEL_INFO', 0b00010);
define('AKARI_LOG_LEVEL_WARN', 0b00100);
define('AKARI_LOG_LEVEL_ERROR', 0b01000);
define('AKARI_LOG_LEVEL_FATAL', 0b10000);
define('AKARI_LOG_LEVEL_NONE', 0);
define('AKARI_LOG_LEVEL_ALL', AKARI_LOG_LEVEL_DEBUG | AKARI_LOG_LEVEL_INFO | AKARI_LOG_LEVEL_WARN | AKARI_LOG_LEVEL_ERROR | AKARI_LOG_LEVEL_FATAL);
define('AKARI_LOG_LEVEL_PRODUCTION', AKARI_LOG_LEVEL_INFO | AKARI_LOG_LEVEL_WARN | AKARI_LOG_LEVEL_ERROR | AKARI_LOG_LEVEL_FATAL);

define('AKARI_URI_AUTO', 0);
define('AKARI_URI_PATHINFO', 1);
define('AKARI_URI_QUERYSTRING', 2);
define('AKARI_URI_REQUESTURI', 3);


define("CLI_MODE", php_sapi_name()=="cli" ? TRUE : FALSE);