<?php
namespace Akari\system\router;

use Akari\Context;
use Akari\NotFoundClass;
use Akari\system\http\Request;
use Akari\system\ioc\DIHelper;
use Akari\system\ioc\DINotRegistered;
use Akari\system\ioc\Injectable;
use Akari\system\result\Result;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ValueHelper;

!defined("AKARI_PATH") && exit;

Class Dispatcher extends Injectable{

    use Logging, ValueHelper;

    private $_controllerName;
    private $_fullControllerName;
    private $_actionName;
    
    /**
     * CLI模式下 任务路径的分发
     *
     * @param string $URI uri路径
     * @return bool|string
     * @throws MethodNameNotAllowed
     * @throws NotFoundClass
     * @throws NotFoundURI
     */
    public function dispatchTask($URI = '') {
        list($taskName, $methodName) = explode("/", $URI);
        $taskName = $taskName. "Task";

        $cls = implode(NAMESPACE_SEPARATOR, [Context::$appBaseNS, 'task', $taskName]);
        try {
            $isExistCls = !!class_exists($cls);
        } catch (NotFoundClass $e) {
            throw new NotFoundURI($methodName, $cls);
        }
       
        return $this->doAction($cls, $methodName);
    }

    /**
     * @param string $uri
     * @param $baseDir
     * @param string $ext
     * @return bool|string
     * @throws DispatcherException
     */
    public function findWay($uri, $baseDir, $ext = '.php') {
        if (!is_array($uri))    $uri = explode('/', $uri);

        $baseDirPath = implode(DIRECTORY_SEPARATOR, [Context::$appEntryPath, $baseDir, '']);
        $uriLevels = count($uri);
        if ($uriLevels > 10) {
            throw new DispatcherException('invalid URI');
        }
        
        for ($i = 0; $i < $uriLevels - 1; $i++) {
            $fileName = array_pop($uri);
            $filePath = implode(DIRECTORY_SEPARATOR, $uri);

            $path = $baseDirPath. $filePath. DIRECTORY_SEPARATOR. $fileName. $ext;
            if(file_exists($path)){
                return realpath($path);
            }

            $path = $baseDirPath. $filePath. DIRECTORY_SEPARATOR. "default". $ext;
            if(file_exists($path)){
                return realpath($path);
            }
        }

        if(file_exists($path = $baseDirPath. array_shift($uri). $ext)){
            return realpath($path);
        }

        if(file_exists($path = $baseDirPath. "default". $ext)){
            return realpath($path);
        }

        return FALSE;
    }

    /**
     * @param $uri
     * @return array|mixed|null
     * @throws DispatcherException -> 层级过多时抛出
     *
     * @return bool
     */
    public function appInvoke($uri) {
        $parts = explode("/", $uri);
        $method = array_pop($parts);
        $class = ucfirst(array_pop($parts)).'Action';
        
        //避免爆炸
        if ($class == 'Action') $class = 'IndexAction';

        $cls = $this->getAppActionNS(). NAMESPACE_SEPARATOR. implode(NAMESPACE_SEPARATOR, array_merge($parts, [$class]));
        $isExistCls = False;
        
        try {
            $isExistCls = !!class_exists($cls);
        } catch (NotFoundClass $e) {
        }

        if ($isExistCls) {
            $this->_actionName = $method;
            $this->_fullControllerName = $cls;
            $this->_controllerName = implode(NAMESPACE_SEPARATOR,array_merge($parts, [$class]));
            
            Context::$appExecute = [$cls, $method];
            Context::$appEntryMethod = $method;
            Context::$appEntryName = implode(DIRECTORY_SEPARATOR, $parts). DIRECTORY_SEPARATOR . $class. '.php';
        }
    }
    
    protected function getAppActionNS() {
        $config = Context::env('bindDomain', NULL, []);
        
        if (isset($config[$this->request->getHost()])) {
            return Context::$appBaseNS. $config[$this->request->getHost()];
        }
        
        if (isset($config['default'])) {
            return Context::$appBaseNS. $config['default'];
        }
        
        return Context::$appBaseNS. NAMESPACE_SEPARATOR. 'action';
    }

    /**
     * 根据URI分配路径
     *
     * @return Result|NULL
     * @throws DispatcherException
     * @throws NotFoundURI
     */
    public function dispatch() {
        if (empty($this->getControllerName())) {
            throw new NotFoundURI(Context::$uri);
        }
        
        return $this->doAction($this->_fullControllerName, $this->getActionName());
    }

    protected function doAction($cls, $method) {
        if (empty($method)) {
            $method = "indexAction";
        }

        if ($method[0] == '_') {
            throw new MethodNameNotAllowed($method, $cls);
        }
        $clsObj = new $cls();

        if (method_exists($clsObj, $method. "Action")) {
            $method = $method. "Action";
        } elseif (method_exists($clsObj, '_handle')) {
            $method = '_handle';
        } else {
            throw new NotFoundURI($method, $cls);
        }

        if (method_exists($clsObj, '_pre')) {
            $beforeResult = $clsObj->_pre();
            
            // 如果_pre()有返回Result 那么也和trigger一致,直接中断
            if (!empty($beforeResult) && $beforeResult instanceof Result) {
                return $beforeResult;
            }
        }

        /*
            如果说 这个path是一个class文件 Result需要调用对应方法执行
            为何不允许在Class上用反射绑定类关系? 复杂用RequestModel  获得用GP或者$this->request
            避免程序偷懒导致变量值无法正确的过滤
        */
        $result = $clsObj->$method();

        if (method_exists($clsObj, '_after')) {
            $result = $clsObj->_after($result);
        }
        return $result;
    }
    
    public function getControllerName() {
        return $this->_controllerName;
    }
    
    public function getActionName() {
        return $this->_actionName;
    }
}

Class NotFoundURI extends \Exception {

    public function __construct($methodName, $className = NULL, $previous = NULL) {
        $methodName = is_array($methodName) ? implode("/", $methodName) : $methodName;
        $this->message = "not found $methodName on ". ($className == NULL ? " direct " : $className);
        $this->previous = $previous;
    }

}

Class MethodNameNotAllowed extends \Exception {

}

Class DispatcherException extends \Exception{

}