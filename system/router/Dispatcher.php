<?php
namespace Akari\system\router;

use Akari\Context;
use Akari\NotFoundClass;
use Akari\system\http\Request;
use Akari\system\result\Result;
use Akari\utility\helper\Logging;

!defined("AKARI_PATH") && exit;

Class Dispatcher{

    use Logging;

    private $config;
    private static $r;

    /**
     * 单例
     * @return Dispatcher
     */
    public static function getInstance() {
        if (self::$r == null) {
            self::$r = new self();
        }
        return self::$r;
    }

    /**
     * 构造函数
     */
    private function __construct(){
        $this->config = Context::$appConfig;
    }

    /**
     * CLI模式下 任务路径的分发
     *
     * @param string $URI uri路径
     * @return bool|string
     * @throws MethodNameNotAllowed
     * @throws NotFoundClass
     * @throws NotFoundURI
     */
    public function invokeTask($URI = ''){
        list($taskName, $methodName) = explode("/", $URI);
        $taskName = $taskName. "Task";

        $path = implode(DIRECTORY_SEPARATOR, [
            Context::$appEntryPath, "task",
            $taskName.".php"
        ]);

        if(!file_exists($path)){
            throw new NotFoundClass($taskName);
        }

        $cls = implode(NAMESPACE_SEPARATOR, [Context::$appBaseNS, 'task', $taskName]);
        return $this->doAction($cls, $methodName);
    }

    /**
     * 临时注册某个路由
     *
     * @param string $re URL匹配正则
     * @param string $url 重写到哪个文件
     */
    public function register($re, $url){
        Context::$appConfig->uriRewrite[$re] = $url;
    }

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
                return $path;
            }

            $path = $baseDirPath. $filePath. DIRECTORY_SEPARATOR. "default". $ext;
            if(file_exists($path)){
                return $path;
            }
        }

        if(file_exists($path = $baseDirPath. array_shift($uri). $ext)){
            return $path;
        }

        return FALSE;
    }

    /**
     * 由于应用使用了Context::$appBaseURL作为基础连接
     * 但某些时候，如ajax之类 必须保证http和https在一个页面才可以触发
     *
     * @param string $URI URI地址
     * @return string
     */
    public function rewriteBaseURL($URI) {
        $isSSL = Request::getInstance()->isSSL();
        $URI = preg_replace('/https|http/i', $isSSL ? 'https' : 'http' , $URI);

        return $URI;
    }

    /**
     * @param string $URI
     * @return array|mixed
     */
    public function getRewriteURL($URI) {
        $list = explode("/", $URI);
        $URLRewrite = Context::$appConfig->uriRewrite;

        foreach($URLRewrite as $key => $value){
            if (preg_match($key, $URI)) {
                if ( is_callable($value) ) {
                    $value = $value($URI);
                    if ($value) {
                        $list = $value;
                        break;
                    }
                } else {
                    $result = preg_split($key, $URI, -1, PREG_SPLIT_DELIM_CAPTURE);
                    foreach ($result as $k => $v) {
                        $value = str_replace("@".$k, $v, $value);
                    }

                    $list = $value;
                    break;
                }
            }
        }

        return $list;
    }

    /**
     * 根据URI分配路径
     *
     * @param string $uri
     * @return Result
     * @throws DispatcherException
     * @throws NotFoundURI
     */
    public function invoke($uri = ''){
        $uri = $this->getRewriteURL($uri);

        // 首先查找有没有对应的BaseAction方法 如果有的话 直接invoke到方法执行
        $parts = $uri;
        $method = array_pop($parts);
        $class = ucfirst(array_pop($parts)).'Action';

        $cls = Context::$appBaseNS. NAMESPACE_SEPARATOR. 'action'. NAMESPACE_SEPARATOR. implode(NAMESPACE_SEPARATOR, array_merge($parts, [$class]));
        try {
            if (class_exists($cls)) {
                Context::$appEntryName = implode(DIRECTORY_SEPARATOR, $parts). DIRECTORY_SEPARATOR. $class.".php";
                return $this->doAction($cls, $method);
            }
        } catch (NotFoundClass $e) {
            $path = $this->findWay($uri, 'action');
            if (!$path) {
                throw new NotFoundURI($uri);
            }

            Context::$appEntryName = str_replace([Context::$appEntryPath, 'action/'], '', $path);

            // 如果没有找到类 就按照默认查询方式操作
            $conResult = require($path);
            if (isset($conResult)) {
                return $conResult;
            }
        }
    }


    private function doAction($cls, $method) {
        if ($method[0] == '_') {
            throw new MethodNameNotAllowed($method, $cls);
        }
        $clsObj = new $cls();

        if (!method_exists($clsObj, $method)) {
            if (method_exists($clsObj, '_default')) {
                $method = '_default';
            } else {
                throw new NotFoundURI($method, $cls);
            }
        }

        Context::$appEntryMethod = $method;

        // 如果说 这个path是一个class文件 Result需要调用对应方法执行
        // 为何不允许在Class上用反射绑定类关系? 复杂用RequestModel  获得用GP
        // 避免程序员偷懒只指定不做处理
        $result = $clsObj->$method();

        // 如果有_after方法 则会将获得的result转发给_after
        if (method_exists($clsObj, '_after')) {
            $result = $clsObj->_after($result);
        }

        return $result;
    }
}

Class NotFoundURI extends \Exception {

    public function __construct($methodName, $className = NULL) {
        $methodName = is_array($methodName) ? implode("/", $methodName) : $methodName;
        $this->message = "not found $methodName on ". ($className == NULL ? " direct " : $className);
    }

}

Class MethodNameNotAllowed extends \Exception {

}

Class DispatcherException extends \Exception{

}