<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:32
 */

namespace Akari\system\exception;

use Akari\system\event\Trigger;
use Akari\system\event\Listener;
use Akari\system\ioc\Injectable;
use Akari\system\result\Processor;

class ExceptionProcessor extends Injectable{

    /**
     * 异常被执行时
     *
     * {class: string, message: string, file: string, line: int}
     */
    const EVENT_EXCEPTION_EXECUTE = "coreException.exception";
    
    private $_isFired = FALSE;

    public static $p;
    public static function getInstance() {
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

    /** @var  BaseExceptionHandler */
    protected $handler;

    /**
     * 设置Handler
     * 
     * @param $clsPath
     * @param bool $setFkHandler 是否将其作为框架异常的处理 开启时notFoundTemplate这些框架异常处理也会无效
     */
    public function setHandler($clsPath, $setFkHandler = FALSE) {
        if(!isset($this->handler)){
            set_error_handler(array(self::$p, 'processError'), error_reporting());
            set_exception_handler(array(self::$p, 'processException'));
            register_shutdown_function(array(self::$p, 'processFatal'));
        }

        $this->handler = new $clsPath();
        if ($setFkHandler) {
            $this->fkExceptionHandler = $this->handler;
        }
    }

    public function processError($errorNo, $errorMessage, $errorFile, $errorLine, $context) {
        throw new \ErrorException($errorMessage, 0, $errorNo, $errorFile, $errorLine);
    }
    
    public function processException($ex) {
        if (!is_object($ex)) {
            throw new FatalException("Unknown Exception: " . $ex, __FILE__, __LINE__, E_USER_ERROR);
        } else {
            if (version_compare(PHP_VERSION, '7.0', '>=')) {
                if ($ex instanceof \Error) {
                    $ex = new PHPErrorException($ex);
                }
            }
        }

        Listener::fire(self::EVENT_EXCEPTION_EXECUTE, $ex);

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

        /** @var Processor $processor */
        $processor = $this->getDI()->getShared("processor");
        $processor->processResult($result);

        if (!$this->_isFired) { // 防止循环调用
            Trigger::handle(Trigger::TYPE_APPLICATION_OUTPUT, NULL);
            $this->_isFired = TRUE;
        }
        
        $this->response->send();
    }

    public function setFrameworkExceptionHandler(BaseExceptionHandler $handler) {
        $this->fkExceptionHandler = $handler;
    }

    public function isFatal($exceptionType) {
        return in_array($exceptionType, [
            E_ERROR,
            E_PARSE,
            E_CORE_ERROR,
            E_USER_ERROR,
            E_COMPILE_ERROR
        ]);
    }

    public function processFatal() {
        $fatal = error_get_last();
        if (!$fatal) {
            return FALSE;
        }


        if ($this->isFatal($fatal['type'])) {
            $ex = new FatalException($fatal['message'], $fatal['file'], $fatal['line'], $fatal['type']);
            $this->processException($ex);
        }
    }
}
