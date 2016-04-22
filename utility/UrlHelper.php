<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/4/21
 * Time: 下午9:10
 */

namespace Akari\utility;

use Akari\Context;
use Akari\system\ioc\Injectable;
use Akari\system\security\Security;

class UrlHelper extends Injectable{
    
    protected $instance = NULL;

    public function get($path, $args = [], $withToken = False) {
        if (!empty(Context::$appConfig->uriGenerator)) {
            if (empty($this->instance)) {
                $cls = Context::$appConfig->uriGenerator;
                $this->instance = new $cls();
            }

            return $this->instance->get($path, $args, $withToken);
        }
        
        return $this->createBaseUrl($path, $args, $withToken);
    }
    
    public function createBaseUrl($path, $args, $withToken) {
        if ($withToken && Context::$appConfig->csrfTokenName) {
            $args[ Context::$appConfig->csrfTokenName ] = Security::getCSRFToken();
        }

        return $path. (!empty($args) ? ("?". http_build_query($args)) : '');
    }
    
}