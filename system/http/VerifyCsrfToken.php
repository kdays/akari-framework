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
use Akari\system\security\CSRFVerifyFailed;
use Akari\system\security\Security;
use Akari\utility\helper\ValueHelper;

class VerifyCsrfToken extends Injectable{
    
    use ValueHelper;
    
    public function getToken() {
        $key = $this->_getValue(Security::KEY_TOKEN, NULL, $this->request->getUserIP());

        $str = "CSRF-". Context::$appConfig->appName. $key;
        $token = substr(md5($str) ,7, 9);
        return $token;
    }
    
    public function verifyToken() {
        $tokenName = Context::$appConfig->csrfTokenName;
        $token = NULL;

        if (!empty($_COOKIE[$tokenName])) {
            $token = $_COOKIE[$tokenName];
        }
        
        if (!empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
        }

        if (!empty($_REQUEST[$tokenName])) {
            $token = $_REQUEST[$tokenName];
        }

        if($token != $this->getToken()){
            throw new CSRFVerifyFailed();
        }
    }
    
    public function autoVerify() {
        $config = Context::$appConfig;
        $needVerify = (!CLI_MODE && $config->autoPostTokenCheck);

        if (!empty($config->csrfTokenName) && $needVerify) {
            $tokenValue = $this->getToken();

            if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                setcookie($config->csrfTokenName, $tokenValue, NULL, $config->cookiePath, $config->cookieDomain);
            } else {
                $this->verifyToken();
            }
        }
    }
    
}