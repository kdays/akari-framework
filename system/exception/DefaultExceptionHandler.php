<?php
Class DefaultExceptionHandler{
	public function handleException(Exception $ex){
		HttpStatus::setStatus(HttpStatus::INTERNAL_SERVER_ERROR);
		$trace = $ex->getTrace();
		if (@$trace[0]['file'] == '') {
			unset($trace[0]);
			$trace = array_values($trace);
		}
		$file = @$trace[0]['file'];
		$line = @$trace[0]['line'];

		Logging::_logErr($ex->getMessage()."\t(".$file.":".$line.")");
		$this->msg($ex->getMessage(), $file, $line, $trace, $ex->getCode());
	}

	public function handleFatal($error, $message, $file, $line){
		Logging::_logFatal($message."\t(".$file.":".$line.")");
		$this->msg($message, $file, $line, array(), $error);
	}

	/**
	 * 错误处理
	 * 
	 * @param string $message
	 * @param string $file 异常文件
	 * @param int $line 错误发生的行
	 * @param array $trace
	 * @param int $errorcode 错误代码
	 * @throws WindFinalException
	 */
	public function msg($message, $file, $line, $trace, $errorcode) {
		$log = $message . "\r\n" . str_replace(Context::$appBasePath, '', $file) . ":" . $line . "\r\n";
		list($fileLines, $trace) = self::crash($file, $line, $trace);
		foreach ($trace as $key => $value) {
			$log .= $value . "\r\n";
		}

		$fileLineLog = "";
		foreach($fileLines as $key => $value){
			$value = str_replace("  ", "<span class='w-block'></span>", $value);
			if($key == $line - 1){
				$fileLineLog .= "<li class='current'>$value</li>";
			}else{
				$fileLineLog .= "<li>$value</li>\n";
			}
		}
		
		$version = AKARI_VERSION;
		$build = AKARI_BUILD;
		$file = str_replace(Context::$appBasePath, '', $file);
		if(CLI_MODE){
			fwrite(STDOUT, date('[Y-m-d H:i:s] '). $message ."($file:$line)". PHP_EOL);
		}else{
			require(AKARI_PATH."template/error_trace.htm");exit;
		}
	}

	/**
	 * 错误信息处理方法
	 *
	 * @param string $file
	 * @param string $line
	 * @param array $trace
	 */
	public static function crash($file, $line, $trace) {
		$msg = '';
		$count = count($trace);
		$padLen = strlen($count);
		foreach ($trace as $key => $call) {
			if (!isset($call['file']) || $call['file'] == '') {
				$call['file'] = 'Internal Location';
				$call['line'] = 'N/A';
			}else{
				$call['file'] = str_replace(Context::$appBasePath, '', $call['file']);
			}
			$traceLine = '#' . str_pad(($count - $key), $padLen, "0", STR_PAD_LEFT) . ' ' . self::getCallLine(
				$call);
			$trace[$key] = $traceLine;
		}
		$fileLines = array();
		if (is_file($file)) {
			$currentLine = $line - 1;
			$fileLines = explode("\n", file_get_contents($file, null, null, 0, 10000000));
			$topLine = $currentLine - 5;
			$fileLines = array_slice($fileLines, $topLine > 0 ? $topLine : 0, 10, true);
			
			if (($count = count($fileLines)) > 0) {
				$padLen = strlen($count);
				foreach ($fileLines as $line => &$fileLine){
					$fileLine = " <b>" .str_pad($line + 1, $padLen, "0", STR_PAD_LEFT) . "</b> " . htmlspecialchars(str_replace("\t", 
							"    ", rtrim($fileLine)), null, "UTF-8");
				}
			}
		}

		return array($fileLines, $trace);
	}
	
	/**
	 * @param array $call
	 * @return string
	 */
	private static function getCallLine($call) {
		$call_signature = "";
		if (isset($call['file'])) $call_signature .= $call['file'] . " ";
		if (isset($call['line'])) $call_signature .= ":" . $call['line'] . " ";
		if (isset($call['function'])) {
		    $call_signature .= "<span class=func>";
		    if(isset($call['class'])) $call_signature .= "$call[class]->";
			$call_signature .= $call['function']."(";
			if (isset($call['args'])) {
				foreach ($call['args'] as $arg) {
					if (is_string($arg))
						$arg = '"' . (strlen($arg) <= 64 ? $arg : substr($arg, 0, 64) . "…") . '"';
					else if (is_object($arg))
						$arg = "[Instance of '" . get_class($arg) . "']";
					else if ($arg === true)
						$arg = "true";
					else if ($arg === false)
						$arg = "false";
					else if ($arg === null)
						$arg = "null";
					else if (is_array($arg))
						$arg = '[Array]';
					else
						$arg = strval($arg);
					$call_signature .= $arg . ',';
				}
				$call_signature = trim($call_signature, ',') . ")</span>";
			}
		}
		return $call_signature;
	}
}