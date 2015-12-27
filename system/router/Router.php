<?php
namespace Akari\system\router;

use Akari\Context;
use Akari\system\http\Request;
use Akari\utility\helper\ValueHelper;

!defined("AKARI_PATH") && exit;

Class Router{

    use ValueHelper;

    private $config;
    private $request;
    private static $r;

    public static function getInstance() {
        if (self::$r == null) {
            self::$r = new self();
        }
        return self::$r;
    }

    private function __construct(){
        $this->request = Request::getInstance();
        $this->config = Context::$appConfig;
    }

    private function clearURI($uri){
        $queryString = $_SERVER['QUERY_STRING'];
        if(strlen($queryString) > 0){
            $uri = substr($uri, 0, -strlen($queryString)  -1);
        }

        $scriptName = Context::$appEntryName;
        $scriptNameLen = strlen($scriptName);
        if (substr($uri, 1, $scriptNameLen) === $scriptName) {
            $uri = substr($uri, $scriptNameLen + 1);
        }

        return $uri;
    }

    public function resolveURI(){
        $uri = null;

        $config = $this->config;
        switch($config->uriMode){
            case AKARI_URI_AUTO:
                $uri = $this->request->getPathInfo();
                if(empty($uri)){
                    if(isset($_GET['uri']))	$uri = $_GET['uri'];
                    if(empty($uri)){
                        $uri = $this->clearURI($this->request->getRequestURI());
                    }
                }
                break;

            case AKARI_URI_PATHINFO:
                $uri = $this->request->getPathInfo(); break;

            case AKARI_URI_QUERYSTRING:
                if(isset($_GET['uri']))	$uri = $_GET['uri']; break;

            case AKARI_URI_REQUESTURI:
                $uri = $this->clearURI($this->request->getRequestURI());break;
        }

        $urlInfo = parse_url(Context::$appConfig->appBaseURL);

        // 如果基础站点URL不是根目录时
        if (isset($urlInfo['path']) && $urlInfo['path'] != '/') {
            $uriPrefix = rtrim($urlInfo['path'], '/');
            $uriPrefixLength = strlen($uriPrefix);
            if (substr($uri, 0, $uriPrefixLength) === $uriPrefix) {
                $uri = substr($uri, $uriPrefixLength);
            }
        }

        $uri = preg_replace('/\/+/', '/', $uri); //把多余的//替换掉..

        if(!$uri || $uri == '/' || $uri == '/'.Context::$appEntryName){
            $uri = $config->defaultURI;
        }

        $uriParts = explode('/', $uri);
        if(count($uriParts) < 3){
            //$uri = dirname($config->defaultURI).'/'.array_pop($uriParts);
        }

        if (substr($uri, -1) === '/') {
            $uri .= 'index';
        }else{
            if (!empty($config->uriSuffix)) {
                $suffix = substr($uri, -strlen($config->uriSuffix));
                if ($suffix === $config->uriSuffix) {
                    $uri = substr($uri, 0, -strlen($config->uriSuffix));
                } else {
                    throw new \Exception('Invalid URI');
                }
            }
        }

        if($uri[0] == '/'){
            $uri = substr($uri, 1);
        }

        return $uri;
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
     * @param $now
     * @param $re
     *
     * @return array|bool
     */
    public function matchURLByString($now, $re) {
        $uri = explode("/", $re);
        $now = explode("/", $now);

        $uri = array_filter($uri);
        $now = array_filter($now);

        if (count($uri) != count($now)) {
            return false;
        }
        
        $block = [];
        
        foreach ($now as $key => $value) {
            if (!isset($uri[$key])) {
                return False;
            }
            
            /**
             * @todo 可处理某些特殊非标准的 /后面跟特殊字符的 比如 /subject/{id}!index这种处理
             * 不支持 index_{id}
            **/
            if (substr($uri[$key], 0, 1) == '{') {
                $lastUriPos = strpos($uri[$key], '}');
                $maxUriPos = strlen($uri[$key]);
                
                if ($lastUriPos + 1 == $maxUriPos) {
                    $block[ substr($uri[$key], 1, -1) ] = $value;
                } else {
                    $nonKeyword = substr($uri[$key], $lastUriPos + 1);
                    $nonKeywordLen = strlen($nonKeyword);
                    if ($nonKeyword != substr($value, -$nonKeywordLen)) {
                        return false;
                    }
                    
                    $block[ substr($uri[$key], 1, $lastUriPos - 1) ] = substr($value, 0, -$nonKeywordLen);
                }
                
                continue;
            }

            // 不然匹配内容是否一致
            if ($value == $uri[$key] || $uri[$key] == "*") {
                if ($uri[$key] == '*') {
                    $block['Mark:'. $key] = $now[$key];
                }
                continue;
            }

            return False;
        }
        
        return $block;
    }

    const REWRITE_MODE_REGEXP = 0;
    const REWRITE_MODE_STR = 1;
    const REWRITE_MODE_NONE = 2;

    /**
     * @param string $URI
     * @param null|array $rule
     *
     * @return array|mixed
     */
    public function getRewriteURL($URI, $rule = NULL) {
        $matchResult = False;
        $URLRewrite = $rule === NULL ? Context::$appConfig->uriRewrite : $rule;

        // 让路由重写时支持METHOD设定 而非CALLBACK时处理
        $nowRequestMethod = Request::getInstance()->getRequestMethod();
        $allowMethodHeader = ['GET', 'POST', 'PUT', 'DELETE', 'GP'];
        $methodRegexp = "/^(GET|POST|PUT|DELETE|GP):(.*)/";

        /**@var mixed|callable|URL $value**/
        foreach($URLRewrite as $re => $value){
            $matchMode = self::REWRITE_MODE_STR;
            if (substr($re, 0, 1) == '!') {
                $matchMode = self::REWRITE_MODE_REGEXP;
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

            if (substr($re, 0, 1) == '/' && $matchMode != self::REWRITE_MODE_REGEXP) {
                $matchMode = self::REWRITE_MODE_REGEXP;
            }

            // 判定方式
            if ($matchMode == self::REWRITE_MODE_REGEXP) {
                $isMatched = preg_match($re, $URI);
            } else {
                $isMatched = $this->matchURLByString($URI, $re);
            }

            if ($isMatched === FALSE) continue;

            if (is_callable($value)) {
                $matchResult = $value($URI);
                if ($matchResult) break;
            } else {
                $value = str_replace(".", "/", $value);
                if ($matchMode == self::REWRITE_MODE_REGEXP) {
                    $result = preg_split($re, $URI, -1, PREG_SPLIT_DELIM_CAPTURE);
                    foreach ($result as $k => $v) {
                        $value = str_replace("@".$k, $v, $value);
                    }

                    $matchResult = $value;
                } else {
                    $matchResult = $value;
                    foreach ($isMatched as $k => $v) {
                        $this->_setValue("U:". $k, $v);
                    }
                }
                
                // ?后的参数视为URL本来就有的参数处理 而不是存入U:变量
                if(strpos($matchResult, "?") !== FALSE) {
                    $result = [];
                    parse_str(substr($matchResult, strpos($matchResult, "?") + 1), $result);

                    foreach ($result as $k => $v) {
                        if (substr($v, 0, 1) == ':') {
                            $v = isset($isMatched[substr($v, 1)]) ? $isMatched[substr($v, 1)] : ''; 
                        }
                        
                        $_GET[$k] = $v;
                        if (!array_key_exists($k, $_REQUEST)) {
                            $_REQUEST[$k] = $v;
                        }
                    }
                    
                    $matchResult = substr($matchResult, 0, strpos($matchResult, "?"));
                }

                if (strpos($matchResult, "*") !== false) {
                    $matches = explode("/", $matchResult);

                    foreach ($matches as $k => $match) {
                        if ($match == '*' && isset($isMatched['Mark:'.($k + 1)])) {
                            $matches[$k] = $isMatched['Mark:'. ($k + 1)];
                        }
                    }

                    $matchResult = implode("/", $matches);
                }


                break;
            }
        }
        
        return empty($matchResult) ? $URI : $matchResult;
    }
}