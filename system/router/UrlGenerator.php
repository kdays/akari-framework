<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-27
 * Time: 16:52
 */

namespace Akari\system\router;

use Akari\Core;
use Akari\system\ioc\Injectable;
use Akari\system\util\helper\AppValueTrait;
use Akari\system\security\helper\VerifyCSRFToken;

class UrlGenerator extends Injectable {

    use AppValueTrait;

    public function get(string $path, array $args = [], $withToken = FALSE) {
        return $this->createBaseUrl($path, $args, $withToken);
    }

    public function getStaticUrl(string $path) {
        return str_replace('//', '/', '/static/' . $path);
    }

    public function createBaseUrl($path, $args, $withToken) {
        if ($withToken) {
            $tokenKey = $this->_getConfigValue('csrfTokenName', '_akari');
            list($_, $tokenValue) = VerifyCSRFToken::getTokenParameter();

            if ($tokenKey) {
                $args[ $tokenKey ] = $tokenValue;
            }
        }

        return $path . (!empty($args) ? ("?" . http_build_query($args)) : '');
    }

    public function getApiUrl(string $class, string $method) {
        $class = str_replace(Core::$appNs, '', $class);
        $pathNames = [];
        foreach (explode(NAMESPACE_SEPARATOR, $class) as $part) {
            if (empty($part)) continue;
            if ($part == 'action') continue;

            $part[0] = strtolower($part[0]);

            foreach (['Api', 'Action'] as $suffix) {
                if (substr($part, -strlen($suffix)) === $suffix) {
                    $part = substr($part, 0, -strlen($suffix));
                }
            }

            $pathNames[] = $part;
        }

        foreach (['Action'] as $suffix) {
            if (substr($method, -strlen($suffix)) === $suffix) {
                $method = substr($method, 0, -strlen($suffix));
            }
        }

        return implode('/', $pathNames) . "/" . $method;
    }

}
