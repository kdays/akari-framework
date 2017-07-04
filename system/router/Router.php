<?php
namespace Akari\system\router;

use Akari\Context;
use Akari\system\ioc\Injectable;
use Akari\utility\helper\ValueHelper;
use Akari\system\exception\AkariException;

class Router extends Injectable {

    use ValueHelper;

    private $config;
    private $params = [];

    public function __construct() {
        $this->config = Context::$appConfig;
    }

    private function clearURI($uri) {
        $queryString = $this->request->getQueryString();
        if(strlen($queryString) > 0){
            $uri = substr($uri, 0, -strlen($queryString) - 1);
        }

        $scriptName = Context::$appEntryName;
        $scriptNameLen = strlen($scriptName);
        if (substr($uri, 1, $scriptNameLen) === $scriptName) {
            $uri = substr($uri, $scriptNameLen + 1);
        }

        return $uri;
    }

    public function resolveURI() {
        $uri = NULL;

        $config = $this->config;
        switch($config->uriMode){
            case AKARI_URI_AUTO:
                $uri = $this->request->getUrlPathInfo();
                if(empty($uri)){
                    if(isset($_GET['_url']))	$uri = $_GET['_url'];
                    if(empty($uri)){
                        $uri = $this->clearURI($this->request->getRequestURI());
                    }
                }
                break;

            case AKARI_URI_PATHINFO:
                $uri = $this->request->getUrlPathInfo(); break;

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

        if(!$uri || $uri == '/' || $uri == '/' . Context::$appEntryName){
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
                    throw new AkariException('Invalid URI');
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
        $isSSL = $this->request->isSSL();
        $URI = preg_replace('/https|http/i', $isSSL ? 'https' : 'http', $URI);

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
            return FALSE;
        }

        $block = [];
        $nowMarkKey = 0;

        foreach ($now as $key => $value) {
            if (!isset($uri[$key])) {
                return FALSE;
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
                        return FALSE;
                    }

                    $block[ substr($uri[$key], 1, $lastUriPos - 1) ] = substr($value, 0, -$nonKeywordLen);
                }

                continue;
            }

            // 不然匹配内容是否一致
            if ($value == $uri[$key] || $uri[$key] == "*") {
                if ($uri[$key] == '*') {
                    $block[$nowMarkKey] = $now[$key];
                    ++$nowMarkKey;
                }
                continue;
            }

            return FALSE;
        }

        return $block;
    }

    const REWRITE_MODE_REGEXP = 0;
    const REWRITE_MODE_STR = 1;
    const REWRITE_MODE_NONE = 2;

    public function getUrlFromRule($URI, $rules = NULL) {
        $rules = $rules === NULL ? Context::env('uriRewrite') : $rules;

        $allowMethods = ['GET', 'POST', 'PUT', 'GP'];
        $nowRequestMethod = $this->request->getRequestMethod();
        $methodRe = "/^(" . implode("|", $allowMethods) . "):(.*)/";
        $matchResult = NULL;

        $this->resetParameters();
        foreach ($rules as $rule => $toName) {
            $matchMode = self::REWRITE_MODE_STR;
            if (substr($rule, 0, 1) == '!') {
                $matchMode = self::REWRITE_MODE_REGEXP;
                $rule = substr($rule, 1);
            }

            // 检查重写类型是否正确 不正确的话直接错误
            preg_match($methodRe, $rule, $methodResult);
            if (isset($methodResult[1]) && in_array($methodResult[1], $allowMethods)) {
                $needMethod = $methodResult[1];
                if ($nowRequestMethod == $needMethod ||
                    ($needMethod == 'GP' && in_array($nowRequestMethod, ['GET', 'POST']))) {
                    $rule = $methodResult[2];
                } else {
                    continue;
                }
            }

            if (substr($rule, 0, 1) == '/' && $matchMode != self::REWRITE_MODE_REGEXP) {
                $matchMode = self::REWRITE_MODE_REGEXP;
            }


            $matches = FALSE;

            // 根据matchMode处理
            if ($matchMode == self::REWRITE_MODE_REGEXP) {
                if (preg_match($rule, $URI)) {
                    $matches = preg_split($rule, $URI, -1, PREG_SPLIT_DELIM_CAPTURE);
                }
            } else {
                $matches = $this->matchURLByString($URI, $rule);
            }

            if ($matches === FALSE) {
                continue;
            }

            if (is_callable($toName)) {
                $matchResult = $toName($URI);
                if ($matchResult) break;

                continue;
            }

            $matchResult = $this->setParams4Rewrite($matches, $toName);
            break;
        }

        return empty($matchResult) ? $URI : $matchResult;
    }

    /**
     * @param $urlMatches
     * @param $toActionName
     * @return mixed|string
     * @internal param $URI
     */
    protected function setParams4Rewrite($urlMatches, $toActionName) {
        $toActionName = str_replace(".", "/", $toActionName);

        // toActionName后面如果有参数的话
        if(strpos($toActionName, "?") !== FALSE) {
            $result = [];
            parse_str(substr($toActionName, strpos($toActionName, "?") + 1), $result);

            foreach ($result as $k => $v) {
                if (substr($v, 0, 1) == ':') {
                    $v = isset($urlMatches[substr($v, 1)]) ? $urlMatches[substr($v, 1)] : '';
                }

                $this->pushParameter($k, $v, TRUE);
            }

            $toActionName = substr($toActionName, 0, strpos($toActionName, "?"));
        }

        // 如果有:处理替换的时候
        if (strpos($toActionName, ":") !== FALSE) {
            $matches = explode("/", $toActionName);
            foreach ($matches as $k => $match) {
                if (substr($match, 0, 1) == ':') {
                    $matchKey = substr($match, 1);

                    if (isset($urlMatches[$matchKey])) {
                        $this->pushParameter($matchKey, $urlMatches[$matchKey], FALSE);

                        $matches[$k] = $urlMatches[$matchKey];
                    }
                }
            }

            $toActionName = implode("/", $matches);
        }

        return $toActionName;
    }

    protected function pushParameter($key, $value, $fromUrl) {
        if ($fromUrl) {
            $_GET[$key] = $value;
            if (!array_key_exists($key, $_REQUEST)) {
                $_REQUEST[$key] = $value;
            }
        }

        $this->params[$key] = $value;
    }

    public function hasParameter($parameter) {
        return array_key_exists($parameter, $this->params);
    }

    public function resetParameters() {
        $this->params = [];
    }

    public function getParameters() {
        return $this->params;
    }

    public function parseArgvParams($args) {
        function resolve(array $params) {
            $now = [];
            foreach ($params as $param) {
                if (preg_match('/^--(\w+)(=(.*))?$/', $param, $matches)) {
                    $name = $matches[1];
                    $now[$name] = isset($matches[3]) ? $matches[3] : TRUE;
                } else {
                    $now[] = $param;
                }
            }

            return $now;
        }

        return resolve($args);
    }
}
