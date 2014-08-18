<?php
namespace Akari\system\exception;

use Akari\system\http\HttpStatus;
use Akari\system\log\Logging;
use \Exception;

Class DefaultExceptionHandler extends BaseExceptionHandler{
	public function handleException(Exception $ex){
		HttpStatus::setStatus(HttpStatus::INTERNAL_SERVER_ERROR);
		$trace = $ex->getTrace();
		if (empty($trace[0]['file'])) {
			unset($trace[0]);
			$trace = array_values($trace);
		}
		$file = @$trace[0]['file'];
		$line = @$trace[0]['line'];

		Logging::_logErr($ex->getMessage()."\t(".$file.":".$line.")");
		$this->_msg($ex->getMessage(), $file, $line, $trace, $ex->getCode());
	}

	public function handleFatal($errorCode, $message, $file, $line){
		Logging::_logFatal($message."\t(".$file.":".$line.")");
		$this->_msg($message, $file, $line, array(), $errorCode);
	}
}