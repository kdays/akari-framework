<?php
namespace Akari\system\router;

use Akari\Context;
use Akari\NotFoundClass;
use Akari\system\http\Request;
use Akari\system\result\Result;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ValueHelper;

!defined("AKARI_PATH") && exit;

Class Dispatcher{

    use Logging, ValueHelper;

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

        if(file_exists($path = $baseDirPath. "default". $ext)){
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

    public function rewriteURLByString($now, $re) {
        $uri = explode("/", $re);
        $now = explode("/", $now);

        $block = [];

        foreach ($now as $key => $value) {
            if (!isset($uri[$key])) {
                return False;
            }

            if (substr($uri[$key], 0, 1) == '{') {
                $k = substr($uri[$key], 1, -1);
                $block[$k] = $value;

                continue;
            }

            // 不然匹配内容是否一致
            if ($value == $uri[$key]) {
                continue;
            }

            return False;
        }

        return $block;
    }

    /**
     * @param string $URI
     * @return array|mixed
     */
    public function getRewriteURL($URI) {
        $matchResult = False;
        $URLRewrite = Context::$appConfig->uriRewrite;

        // 让路由重写时支持METHOD设定 而非CALLBACK时处理
        $nowRequestMethod = Request::getInstance()->getRequestMethod();
        $allowMethodHeader = ['GET', 'POST', 'PUT', 'DELETE', 'GP'];
        $methodRegexp = "/^(GET|POST|PUT|DELETE|GP):(.*)/";

        /**@var mixed|callable|URL $value**/
        foreach($URLRewrite as $re => $value){
            $matchMode = "NORMAL";
            if (substr($re, 0, 1) == '!') {
                $matchMode = "REGEXP";
                $re = substr($re, 1);
            }

            preg_match($methodRegexp, $re, $methodMatch);
            if (isset($methodMatch[1]) && in_array($methodMatch[1], $allowMethodHeader)) {
                $needMethod = $methodMatch[1];
                if ($nowRequestMethod == $needMethod ||
                    ($needMethod == 'GP' && in_array($nowRequestMethod, ['GET', 'POST']))) {
                    $re = $methodMatch[2];
                } else {
                    continue;
                }
            }

            if (substr($re, 0, 1) == '/' && $matchMode != 'REGEXP') {
                $matchMode = "REGEXP";
            }

            // 判定方式
            if ($matchMode == 'REGEXP') {
                $isMatched = preg_match($re, $URI);
            } else {
                $isMatched = $this->rewriteURLByString($URI, $re);
            }


            if (!$isMatched) continue;

            if (is_callable($value)) {
                $matchResult = $value($URI);
                if ($matchResult) break;
            } else {
                $value = str_replace(".", "/", $value);
                if ($matchMode == 'REGEXP') {
                    $result = preg_split($re, $URI, -1, PREG_SPLIT_DELIM_CAPTURE);
                    foreach ($result as $k => $v) {
                        $value = str_replace("@".$k, $v, $value);
                    }

                    $matchResult = $value;

                    if(strpos($matchResult, "?") !== FALSE) {
                        $result = [];
                        parse_str(substr($matchResult, strpos($matchResult, "?") + 1), $result);

                        foreach ($result as $k => $v) {
                            $this->_setValue("U:". $k, $v);
                        }

                        $matchResult = substr($matchResult, 0, strpos($matchResult, "?"));
                    }
                } else {
                    $matchResult = $value;
                    foreach ($isMatched as $k => $v) {
                        $this->_setValue("U:". $k, $v);
                    }
                }

                break;
            }
        }

        return explode("/", empty($matchResult) ? $URI : $matchResult);
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
        $isExistCls = False;

        try {
            $isExistCls = !!class_exists($cls);
        } catch (NotFoundClass $e) {
        }

        if ($isExistCls) {
            Context::$appEntryName = implode(DIRECTORY_SEPARATOR, $parts) . DIRECTORY_SEPARATOR . $class . ".php";
            return $this->doAction($cls, $method);
        }

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


    private function doAction($cls, $method) {
        if (empty($method)) {
            $method = "index";
        }

        if ($method[0] == '_') {
            throw new MethodNameNotAllowed($method, $cls);
        }
        $clsObj = new $cls();

        if (!method_exists($clsObj, $method)) {
            if (method_exists($clsObj, $method. "Action")) {
                $method = $method. "Action";
            } elseif (method_exists($clsObj, '_default')) {
                $method = '_default';
            } else {
                throw new NotFoundURI($method, $cls);
            }
        }

        if (method_exists($clsObj, '_pre')) {
            $clsObj->_pre();
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