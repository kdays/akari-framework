<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-19
 * Time: 16:08
 */

namespace Akari\system\router;

use Akari\Core;
use Akari\system\event\Event;
use Akari\system\result\Result;
use Akari\system\ioc\Injectable;
use Akari\exception\ActionNotFound;
use Akari\exception\LoaderClassNotExists;
use Akari\system\util\helper\AppValueTrait;

class Dispatcher extends Injectable {

    use AppValueTrait;

    const EVENT_APP_START = 'dispatcher.appStart';
    const EVENT_APP_END = 'dispatcher.appEnd';
    const EVENT_NOT_VALID_RESULT = 'dispatcher.notValidResult';

    const DEFAULT_SUFFIX = 'Action';
    const DEFAULT_HANDLER_NAME = '_handle';

    protected $actionName; // 执行目标
    protected $actionMethod; // 执行方法
    protected $actionParameters;
    protected $actionNameSuffix = self::DEFAULT_SUFFIX; // 方法尾部
    protected $actionMethodSuffix = self::DEFAULT_SUFFIX;
    protected $lastResult;

    public function setActionMethodSuffix(string $suffix) {
        $this->actionMethodSuffix = $suffix;
    }

    public function getActionMethodSuffix() {
        return $this->actionMethodSuffix;
    }

    public function setActionNameSuffix(string $suffix) {
        $this->actionNameSuffix = $suffix;

        $pUrl = $this->router->getParsedUrl();
        if ($pUrl) $this->reloadDispatchByURL($pUrl);
    }

    public function getActionNameSuffix() {
        return $this->actionNameSuffix;
    }

    public function initFromUrl(string $uri, array $parameters) {
        $this->reloadDispatchByURL($uri);
        $this->dispatcher->setParameters($parameters);
    }

    protected function reloadDispatchByURL(string $uri) {
        $parts = explode("/", $uri);
        $method = array_pop($parts);

        $classSuffix = $this->getActionNameSuffix();
        $class = ucfirst(array_pop($parts)) . $classSuffix;

        //避免爆炸
        if ($class == $classSuffix) $class = 'Index' . $classSuffix;

        $cls = $this->getAppActionNS() . NAMESPACE_SEPARATOR . implode(NAMESPACE_SEPARATOR, array_merge($parts, [$class]));
        $cls = str_replace(NAMESPACE_SEPARATOR . NAMESPACE_SEPARATOR, NAMESPACE_SEPARATOR, $cls);

        $this->dispatcher->setActionMethod($method);
        $this->dispatcher->setActionName($cls);

        $this->router->setParsedUrl($uri);
    }

    public function getAppActionNs() {
        if (Core::inConsole()) {
            return Core::$appNs . NAMESPACE_SEPARATOR . 'task';
        }

        $config = $this->_getConfigValue('bindDomain', '');
        if (!empty($config)) {
            return $config::handleAppActionNs($this->request);
        }

        return Core::$appNs . NAMESPACE_SEPARATOR . 'action' ;
    }

    public function dispatch() {
        if (empty($this->getActionName())) {
            throw new ActionNotFound("Action not set-up");
        }

        $actionName = $this->getActionName();
        $actionMethod = $this->getActionMethod() . $this->getActionMethodSuffix();

        if (!class_exists($actionName)) {
            throw new ActionNotFound("Action not exists");
        }
        
        return $this->doAction($actionName, $actionMethod);
    }

    protected function doAction(string $class, string $method) {
        if (empty($method) || $method[0] == '_') throw new ActionNotFound("method private");
        $ctl = new $class();
        $methodCaller = NULL;

        if (method_exists($ctl, $method)) {
            $methodCaller = $method;
        } elseif (method_exists($ctl, self::DEFAULT_HANDLER_NAME)) {
            $methodCaller = self::DEFAULT_HANDLER_NAME;
        } else {
            throw new ActionNotFound("Method not exists");
        }

        if (method_exists($ctl, '_pre')) {
            $this->lastResult = $ctl->_pre();
            if ($this->lastResult instanceof Result) {
                return $this->lastResult;
            }
        }

        $this->lastResult = $ctl->$methodCaller();
        if (method_exists($ctl, '_after')) {
            $afterResult = $ctl->_after($this->lastResult);
            if ($afterResult instanceof Result) {
                $this->lastResult = $afterResult;
            }
        }

        if (!is_a($this->lastResult, Result::class)) {
            $defaultResult = new Result(Result::TYPE_NONE, $this->lastResult, []);
            Event::fire(self::EVENT_NOT_VALID_RESULT, [$defaultResult]);

            $this->lastResult = $defaultResult;
        }

        return $this->lastResult;
    }

    public function getLastResult() {
        return $this->lastResult;
    }

    public function getActionName() {
        return $this->actionName;
    }

    public function getActionMethod() {
        return $this->actionMethod;
    }

    public function setActionName(string $actionClass) {
        $this->actionName = $actionClass;
    }

    public function setActionMethod(string $actionMethod) {
        $this->actionMethod = $actionMethod;
    }

    public function setParameters(array $parameters) {
        $this->actionParameters = $parameters;
    }

    public function getParameters() {
        return $this->actionParameters;
    }

    public function getActionPath() { // 用户
        $actionName = $this->getActionName();
        if (empty($actionName)) return NULL;

        try {
            if (!class_exists($actionName)) {
                return NULL;
            }
        } catch (LoaderClassNotExists $e) {
            return NULL;
        }

        $ref = new \ReflectionClass( $actionName );

        return $ref->getFileName();
    }

}
