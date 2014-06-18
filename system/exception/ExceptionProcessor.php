<?php
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

	public function processException(Exception $ex){
		if(!isset($this->handler))	throw $ex;
		if(ob_get_level() > 0)	ob_end_clean();

		Event::fire("coreException.exception", Array(
					"class" => get_class($ex),
					"message" => $ex->getMessage(),
					"file" => $ex->getFile(),
					"line" => $ex->getLine()
				));

		// process app exception
		if(method_exists($ex, "handleException")) {
			$ex->handleException($ex);
		}else{
			$this->handler->handleException($ex);
		}
	}

	public function processError($errno, $errstr, $errfile, $errline, $errcontext) {
		throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
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

				$this->handler->handleFatal($e['type'], $e['message'], $e['file'], $e['line']);
				//throw new Exception($e['message'], $e['type'], 0, $e['file'], $e['line']);
			}
		}
	}

	public function setHandler($clsPath){
		if(!isset($this->handler)){
			set_error_handler(Array(self::$p, 'processError'), error_reporting());
			set_exception_handler(Array(self::$p, 'processException'));
			register_shutdown_function(Array(self::$p, 'processFatal'));
		}

		require(Context::$appBasePath.DIRECTORY_SEPARATOR.$clsPath.".php");
		$cls = basename($clsPath);
		$this->handler = new $cls();
	}
}