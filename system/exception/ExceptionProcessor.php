<?php
namespace Akari\system\exception;

use Akari\system\Event;

!defined("AKARI_PATH") && exit;

Class ExceptionProcessor{

	/**
	 * 异常被执行时
	 *
	 * {class: string, message: string, file: string, line: int}
	 */
	const EVENT_EXCEPTION_EXECUTE = "coreException.exception";
	const EVENT_FATAL_EXECUTE = "coreException.fatal";

	protected $handler;
	public static $p;
	public static function getInstance(){
		if (!isset(self::$p)) {
			self::$p = new self();
		}
		return self::$p;
	}

	public function processException(\Exception $ex){
		if(!isset($this->handler))	throw $ex;
		if(ob_get_level() > 0)	ob_end_clean();

		Event::fire(self::EVENT_EXCEPTION_EXECUTE, [
			"class" => get_class($ex), "message" => $ex->getMessage(),
			"file" => $ex->getFile(), "line" => $ex->getLine()
		]);

		// 策略是这样的 Handler我们觉得是没必要的 (放在exception里)
        // 所以当应用设定了exception时，先检查绑定的是否存在 -> 没有则调用exception里的处理
        // 都没有就报错
        if (method_exists($this->handler, 'handleException')) {
            $this->handler->handleException($ex);
        } else {
            if(method_exists($ex, "handleException")) {
                $ex->handleException($ex);
            }
        }
	}

	public function processError($errorNo, $errorMessage, $errorFile, $errorLine, $context) {
		throw new \ErrorException($errorMessage, 0, $errorNo, $errorFile, $errorLine, $context);
	}

	public function processFatal(){
		$lastError = error_get_last();
		if(!$lastError) {
			return ;
		}

		$doCatch = [E_ERROR, E_PARSE, E_CORE_ERROR, E_USER_ERROR];
		if(in_array($lastError['type'], $doCatch)){
			if(ob_get_level() > 0)	ob_end_clean();

			Event::fire(self::EVENT_FATAL_EXECUTE, [
				"type" => $lastError['type'], "message" => $lastError['message'],
				"file" => $lastError['file'], "line" => $lastError['line']
			]);

            if (method_exists($this->handler, 'handleFatal')) {
                $this->handler->handleFatal($lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);
                return ;
            }

			throw new \Exception($lastError['message'], 0);
		}
	}

	public function setHandler($clsPath){
		if(!isset($this->handler)){
			set_error_handler(Array(self::$p, 'processError'), error_reporting());
			set_exception_handler(Array(self::$p, 'processException'));
			register_shutdown_function(Array(self::$p, 'processFatal'));
		}

		$this->handler = new $clsPath();
	}

	protected static $_dump;
	public static function addDump($var) {
		self::$_dump[] = $var;
	}

	public static function getDump() {
		return self::$_dump;
	}
}