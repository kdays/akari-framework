<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-27
 * Time: 16:52
 */

namespace Akari\system\router;

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

}
