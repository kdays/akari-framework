<?php
namespace Akari\system\exception;

use Akari\system\Event;

!defined("AKARI_PATH") && exit;

Class ExceptionProcessor{
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

		Event::fire("coreException.exception", Array(
					"class" => get_class($ex),
					"message" => $ex->getMessage(),
					"file" => $ex->getFile(),
					"line" => $ex->getLine()
				));

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

	public function processError($errno, $errstr, $errfile, $errline, $errcontext) {
		throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
	}

	public function processFatal(){
		$e = error_get_last();
		if($e){
			$fatalArr = Array(E_ERROR, E_PARSE, E_CORE_ERROR, E_USER_ERROR);
			if(in_array($e['type'], $fatalArr)){
				if(ob_get_level() > 0)	ob_end_clean();

				Event::fire("coreException.fatal", Array(
					"type" => $e['type'],
					"message" => $e['message'],
					"file" => $e['file'],
					"line" => $e['line']
				));

                if (method_exists($this->handler, 'handleFatal')) {
                    $this->handler->handleFatal($e['type'], $e['message'], $e['file'], $e['line']);
                }
				throw new \Exception($e['message'], 0);
			}
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