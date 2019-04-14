<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-19
 * Time: 18:44
 */

namespace Akari\system\router;


use Akari\Core;
use Akari\system\event\Event;
use Akari\system\ioc\Injectable;
use Akari\system\result\Result;
use Akari\system\util\helper\AppValueTrait;
use Akari\system\util\TextUtil;

class Router extends Injectable {

    use AppValueTrait;

    const URI_MODE_AUTO = 0;
    const URI_MODE_PATHINFO = 1;
    const URI_MODE_QUERYSTRING = 2;

    protected $params = [];
    protected $usingUrlParam = FALSE;
    protected $parsedUrl = NULL;

    const DEFAULT_URL_PARAM = '_url';

    public function getParsedUrl() {
        return $this->parsedUrl;
    }

    public function setParsedUrl(string $uri) {
        $this->parsedUrl = $uri;
    }

    protected function pushParameter(string $key, $value, $fromUrl) {
        if ($fromUrl) {
            $_GET[$key] = $value;
            if (!array_key_exists($key, $_REQUEST)) {
                $_REQUEST[$key] = $value;
            }
        }

        $this->params[$key] = $value;
    }

    public function hasParameter(string $key) {
        return array_key_exists($key, $this->params);
    }

    protected function resetParameters() {
        $this->params = [];
    }

    public function getParameters() {
        if (CLI_MODE) {
            $toParameters = $_SERVER['argv'] ?? [];
            array_shift($toParameters);

            return TextUtil::parseArgvParams($toParameters);
        }

        return $this->params ?? [];
    }

    public function getRouterUrlParamName() {
        static $result = NULL;
        if ($result === NULL) {
            $defaultUriParamName = self::DEFAULT_URL_PARAM;
            if (defined('AKARI_ROUTER_URL_PARAM_NAME')) {
                $defaultUriParamName = constant('AKARI_ROUTER_URL_PARAM_NAME');
            }

            $result = $defaultUriParamName;
        }

        return $result;
    }

    public function resolveURI() {
        $uri = NULL;

        switch ($this->_getConfigValue("uriMode", self::URI_MODE_AUTO)) {
            case self::URI_MODE_AUTO:
                $uri = $this->request->getPathInfo();
                if (empty($uri)) {
                    $uriParamName = $this->getRouterUrlParamName();
                    if (isset($_GET[$uriParamName])) {
                        $uri = $_GET[$uriParamName];
                        $this->usingUrlParam = TRUE;
                    }

                    if (empty($uri)) {
                        $uri = $this->request->getRequestURI();
                    }
                }
                break;


            case self::URI_MODE_PATHINFO:
                $uri = $this->request->getPathInfo();
                break;

            case self::URI_MODE_QUERYSTRING:
                $this->usingUrlParam = TRUE;
                $uriParamName = $this->getRouterUrlParamName();

                if(isset($_GET[$uriParamName]))	$uri = $_GET[$uriParamName]; break;
                break;
        }

        $uri = preg_replace('/\/+/', '/', $uri);
        if (empty($uri) || $uri == '/') {
            $uri = $this->_getConfigValue("defaultURI", '/index');
        }

        $matches = explode("/", $uri);
        if (count($matches) == 2) {
            $matches[0] = "index";
        }
        $uri = implode("/", $matches);

        if ($uri[0] != '/') {
            $uri = '/' . $uri;
        }

        $suffix = $this->_getConfigValue("uriSuffix", "");
        if (!empty($suffix)) {
            $uriParam = substr($uri, -strlen($suffix));
            if ($suffix === $uriParam) {
                $uri = substr($uri, 0, -strlen($suffix));
            }
        }

        if (substr($uri, -1) == '/') {
            $uri .= "index";
        }

        return $uri;
    }

    public function getUrlFromRule(string $URI, ?array $rules) {
        if ($rules === NULL) {
            $rules = $this->_getConfigValue('uriRewrite', []);
        }

        $allowMethods = ['GET', 'POST', 'PUT', 'GP'];
        $nowRequestMethod = $this->request->getRequestMethod();
        $matchResult = NULL;

        $this->resetParameters();
        foreach ($rules as $rule => $toName) {
            $matchMode = "STRING";
            if (substr($rule, 0, 1) == '!') {
                $matchMode = 'REGEXP';
                $rule = substr($rule, 1);
            }

            // 匹配URI Method
            $methodArr = explode(":", $rule);
            if (in_array($methodArr[0], $allowMethods)) {
                $needMethod = array_shift($methodArr);
                if ($nowRequestMethod == $needMethod ||
                    ($needMethod == 'GP' && in_array($nowRequestMethod, ['GET', 'POST']))) {
                    $rule = implode(":", $methodArr);
                } else {
                    continue;
                }
            }

            $matches = FALSE;

            // 根据matchMode处理
            if ($matchMode == 'REGEXP') {
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
     * @param $now
     * @param $re
     *
     * @return array|bool
     */
    public function matchURLByString(string $now, string $re) {
        // 如果$re开头是~ 那么取反
        if ($re[0] == "~") {
            return !$this->matchURLByString($now, substr($re, 1));
        }

        $rules = explode("/", $re);
        $now = explode("/", $now);

        $rules = array_filter($rules);
        $now = array_filter($now);

        if (count($rules) != count($now)) {
            return FALSE;
        }

        $block = [];
        $nowMarkKey = 0;

        foreach ($now as $key => $value) {
            if (!isset($rules[$key])) {
                return FALSE;
            }

            $reVal = $rules[$key];

            /**
             * 如果()内的匹配 如/(world|hello)/*
             * 用于某些特定场合的匹配
             */
            if (substr($reVal, 0, 1) == '(') {
                $lastUriPos = strpos($reVal, ')');
                $maxUriPos = strlen($reVal);

                // 尝试匹配括号的内的
                $tryMatch = explode("|", substr($reVal, 1, $lastUriPos - 1));
                if (!in_array($value, $tryMatch)) {
                    return FALSE;
                }

                if ($lastUriPos + 1 != $maxUriPos) {
                    $nonKeyword = substr($reVal, $lastUriPos + 1);
                    $nonKeywordLen = strlen($nonKeyword);
                    if ($nonKeyword != substr($value, -$nonKeywordLen)) {
                        return FALSE;
                    }
                }

                $block[$nowMarkKey] = $value;
                ++$nowMarkKey;
                continue;
            }

            /**
             * @todo 可处理某些特殊非标准的 /后面跟特殊字符的 比如 /subject/{id}!index这种处理
             * 不支持 index_{id}
             **/
            if (substr($reVal, 0, 1) == '{') {
                $lastUriPos = strpos($reVal, '}');
                $maxUriPos = strlen($reVal);

                if ($lastUriPos + 1 == $maxUriPos) {
                    $block[ substr($reVal, 1, -1) ] = $value;
                } else {
                    $nonKeyword = substr($reVal, $lastUriPos + 1);
                    $nonKeywordLen = strlen($nonKeyword);
                    if ($nonKeyword != substr($value, -$nonKeywordLen)) {
                        return FALSE;
                    }

                    $block[ substr($reVal, 1, $lastUriPos - 1) ] = substr($value, 0, -$nonKeywordLen);
                }

                continue;
            }

            // 不然匹配内容是否一致
            if ($value == $rules[$key] || $rules[$key] == "*") {
                if ($rules[$key] == '*') {
                    $block[$nowMarkKey] = $now[$key];
                    ++$nowMarkKey;
                }
                continue;
            }

            return FALSE;
        }

        return $block;
    }

    /**
     * @param $urlMatches
     * @param $toActionName
     * @return mixed|string
     * @internal param $URI
     */
    protected function setParams4Rewrite(array $urlMatches, string $toActionName) {
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

    public function bindPreEvent(string $re, callable $callback) {
        $that = $this;
        Event::register(Dispatcher::EVENT_APP_START, function() use($callback, $that, $re) {
            if ($that->matchURLByString($that->parsedUrl, $re)) {
                $pResult = $callback();
                if ($pResult && $pResult instanceof Result) {
                    $that->processor->process($pResult);
                    $that->response->send();
                }
            }
        });
    }

    public function bindAfterEvent(string $re, callable $callback) {
        $that = $this;
        Event::register(Dispatcher::EVENT_APP_END, function() use($callback, $that, $re) {
            if ($that->matchURLByString($that->parsedUrl, $re)) {
                $pResult = $callback();
                if ($pResult && $pResult instanceof Result) {
                    $that->processor->process($pResult);
                    $that->response->send();
                }
            }
        });
    }

}
