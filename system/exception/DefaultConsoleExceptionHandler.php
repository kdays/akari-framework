<?php
namespace Akari\system\exception;

use Akari\akari;
use Akari\Context;
use Akari\system\log\Logging;
use Exception;

Class DefaultConsoleExceptionHandler extends BaseExceptionHandler {
    public function handleException(Exception $ex){
        $filePath = str_replace(Context::$appBasePath, '', $ex->getFile());

        Logging::_logErr($ex->getMessage(). " on ".$filePath.":".$ex->getLine());
        akari::getInstance()->stop();
    }

    public function handleFatal($errorCode, $message, $file, $line){
        $filePath = str_replace(Context::$appBasePath, '', $file);

        Logging::_logFatal($message."\t(".$filePath.":".$line.")");
        akari::getInstance()->stop();
    }
}