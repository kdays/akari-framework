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
    
    public function makeToken() {
        static $madeToken = NULL;
        if ($madeToken === NULL) {
            $key = $this->_getValue(Security::KEY_TOKEN, NULL, uniqid());

            $str = "CSRF-". Context::$appConfig->appName. $key;
            $madeToken = substr(md5($str) ,7, 9);
        }
        
        return $madeToken;
    }
    
    public function getToken() {
        $config = Context::$appConfig;
        if ($this->request->hasCookie( $config->csrfTokenName )) {
            return $this->request->getCookie( $config->csrfTokenName );
        }
        
        return $this->makeToken();
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
            if (!$this->request->hasCookie($config->csrfTokenName)) {
                $this->response->setCookie($config->csrfTokenName, $this->makeToken());
            }

            if ($this->request->isPost()) {
                $this->verifyToken();
            }
        }
    }
    
}