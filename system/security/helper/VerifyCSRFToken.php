<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-26
 * Time: 19:23
 */

namespace Akari\system\security\helper;

use Akari\system\event\Event;
use Akari\system\ioc\Injectable;
use Akari\system\router\Dispatcher;
use Akari\exception\CSRFVerifyError;
use Akari\system\view\ViewFunctions;
use Akari\system\util\helper\AppValueTrait;

class VerifyCSRFToken extends Injectable {

    use AppValueTrait;

    const DATA_KEY = 'Security:CSRF';
    protected $requestName;

    public function __construct() {
        $this->requestName = $this->_getConfigValue('csrfTokenName', '_akari');
    }

    public function getRequestName() {
        return $this->requestName;
    }

    public function makeToken() {
        static $madeToken = NULL;
        if ($madeToken === NULL) {
            $key = $this->_getValue("csrfTokenKey", uniqid());
            $madeToken = substr(md5($key), 7, 9);

            $this->_setValue(self::DATA_KEY, $madeToken);
        }

        return $madeToken;
    }

    public function getToken() {
        $keyName = $this->getRequestName();
        if ($this->cookie->exists( $keyName )) {
            return $this->cookie->get( $keyName );
        }

        return $this->makeToken();
    }

    public function verifyToken() {
        $tokenName = $this->getRequestName();
        $uToken = NULL;

        if ($this->request->hasServer('HTTP_X_CSRF_TOKEN')) {
            $uToken = $this->request->getServer('HTTP_X_CSRF_TOKEN');
        }

        if ($this->request->has($tokenName)) {
            $uToken = $this->request->get($tokenName);
        }

        $rToken = $this->getToken();
        if ($uToken != $rToken) {
            throw new CSRFVerifyError();
        }
    }


    public function autoVerify() {
        $needVerify = $this->_getConfigValue("autoPostTokenCheck", TRUE);

        if (!empty($this->requestName) && $needVerify) {
            if (!$this->cookie->exists($this->requestName)) {
                $this->cookie->set($this->requestName, $this->makeToken());
            }

            if ($this->request->isPost()) {
                $this->verifyToken();
            }
        }
    }

    public static function register() {
        $instance = new self();

        Event::register(Dispatcher::EVENT_APP_START, function () use ($instance) {
            $instance->autoVerify();
        });

        // CSRF View Register
        ViewFunctions::registerFunction("csrf_token", function () use ($instance) {
            return $instance->getToken();
        });

        ViewFunctions::registerFunction('csrf_form', function () use ($instance) {
            $tokenKey = $instance->getRequestName();

            return sprintf('<input type="hidden" name="%s" value="%s" />',
                $tokenKey,
               $instance->getToken());
        });
    }

}
