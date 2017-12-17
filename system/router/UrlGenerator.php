<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 17/1/25
 * Time: 下午4:49
 */

namespace Akari\system\router;

use Akari\Context;
use Akari\system\security\Security;

class UrlGenerator {

    public function get($path, $args = [], $withToken = FALSE) {
        return $this->createBaseUrl($path, $args, $withToken);
    }

    public function getFullUrl($path, $args = [], $withToken = FALSE) {
        $url = $this->get($path, $args, $withToken);

        return Context::$appConfig->appBaseURL . $url;
    }

    public function createBaseUrl($path, $args, $withToken) {
        if ($withToken && Context::$appConfig->csrfTokenName) {
            $args[ Context::$appConfig->csrfTokenName ] = Security::getCSRFToken();
        }

        return $path . (!empty($args) ? ("?" . http_build_query($args)) : '');
    }

}
