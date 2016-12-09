<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/5/4
 * Time: 上午9:46
 */

namespace Akari\system\http;

use Akari\Context;
use Akari\system\ioc\Injectable;
use Akari\system\security\Security;
use Akari\utility\helper\ValueHelper;
use Akari\system\security\CSRFVerifyFailed;

class VerifyCsrfToken extends Injectable{
    
    use ValueHelper;
    
    public function getToken() {
        $config = Context::$appConfig;
        if (!empty($_COOKIE[ $config->csrfTokenName ])) {
            return $_COOKIE[$config->csrfTokenName];
        }
        
        $key = $this->_getValue(Security::KEY_TOKEN, NULL, $this->request->getUserAgent());
        
        $str = "CSRF-". Context::$appConfig->appName. $key;
        $token = substr(md5($str) ,7, 9);
        return $token;
    }
    
    public function verifyToken() {
        $tokenName = Context::$appConfig->csrfTokenName;
        $uToken = NULL;

        if ($this->request->hasHeader('HTTP_X_CSRF_TOKEN')) {
            $uToken = $this->request->getHeader('HTTP_X_CSRF_TOKEN');
        }
        
        if (!empty($this->request->has($tokenName))) {
            $uToken = $this->request->get($tokenName);
        }

        $rToken = $this->getToken();
        if ($uToken != $rToken) {
            throw new CSRFVerifyFailed($rToken, $uToken);
        }
    }
    
    public function autoVerify() {
        $config = Context::$appConfig;
        $needVerify = (!CLI_MODE && $config->autoPostTokenCheck);

        if (!empty($config->csrfTokenName) && $needVerify) {
            $tokenValue = $this->getToken();

            if (!$this->request->isPost()) {
                setcookie($config->csrfTokenName, $tokenValue, NULL, $config->cookiePath, $config->cookieDomain);
            } else {
                $this->verifyToken();
            }
        }
    }
    
}