<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/4/13
 * Time: 下午1:01
 */

namespace Akari\system\ioc;

/**
 * Class Injectable
 * @package Akari\system\ioc
 * 
 * @property \Akari\system\tpl\View $view
 * @property \Akari\system\http\Request $request
 * @property \Akari\system\http\Response $response
 * @property \Akari\system\http\Session $session
 * @property \Akari\system\http\Cookie $cookies
 * @property \Akari\system\router\Router $router
 * @property \Akari\system\router\Dispatcher $dispatcher
 * @property \Akari\system\tpl\asset\AssetsMgr $assets
 * @property \Akari\system\router\UrlGenerator $url
 * @property \Akari\system\i18n\I18n $lang
 */
abstract class Injectable {

    protected $_dependencyInjector;

    public function setDI(DI $di) {
        $this->_dependencyInjector = $di;
    }

    public function getDI() {
        if (empty($this->_dependencyInjector)) {
            $this->_dependencyInjector = DI::getDefault();
        }

        return $this->_dependencyInjector;
    }

    public function __get($propertyName) {
        $DI = $this->getDI();
        if ($DI->hasShared($propertyName)) {
            return $DI->getShared($propertyName);
        }

        return NULL;
    }

}
