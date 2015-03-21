<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:32
 */

namespace Akari\system\exception;

use Akari\system\event\Event;
use Akari\system\event\Listener;
use Akari\system\result\Processor;
use Akari\utility\helper\ExceptionSetter;

Class ExceptionProcessor {

    /**
     * 异常被执行时
     *
     * {class: string, message: string, file: string, line: int}
     */
    const EVENT_EXCEPTION_EXECUTE = "coreException.exception";

    public static $p;
    public static function getInstance(){
        if (!isset(self::$p)) {
            self::$p = new self();
        }
        return self::$p;
    }

    /**
     * @var FrameworkExceptionHandler
     */
    protected $fkExceptionHandler;
    protected function __construct() {
        $this->fkExceptionHandler = new FrameworkExceptionHandler();
    }

    protected $handler;

    public function setHandler($clsPath){
        if(!isset($this->handler)){
            set_error_handler(Array(self::$p, 'processError'), error_reporting());
            set_exception_handler(Array(self::$p, 'processException'));
            register_shutdown_function(Array(self::$p, 'processFatal'));
        }

        $this->handler = new $clsPath();
    }

    public function processError($errorNo, $errorMessage, $errorFile, $errorLine, $context) {
        throw new \ErrorException($errorMessage, 0, $errorNo, $errorFile, $errorLine);
    }

    public function processException(\Exception $ex) {
        Listener::fire(self::EVENT_EXCEPTION_EXECUTE, ['ex' => $ex]);

        if (ob_get_level() !== 0) {
            ob_end_clean();
        }

        // 检查系统层是否有做处理 没有result返回就调用对方的
        $result = $this->fkExceptionHandler->handleException($ex);

        if (!isset($result)) {
            if (isset($this->handler)) {
                $result = $this->handler->handleException($ex);
            } else {
                throw $ex;
            }
        }

        Processor::getInstance()->processResult($result);
    }

    public function processFatal() {
        $fatal = error_get_last();
        if (!$fatal)    return FALSE;

        if (in_array($fatal['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_USER_ERROR])) {
            $ex = new FatalException($fatal['message'], $fatal['file'], $fatal['line'], $fatal['type']);
            $this->processException($ex);
        }
    }
}

Class FatalException extends \Exception {

    public $logLevel = AKARI_LOG_LEVEL_FATAL;

    protected $type;

    public function __construct($message, $file, $line, $type) {
        $this->message = $message;
        $this->file = $file;
        $this->line = $line;
        $this->type = $type;
    }

}