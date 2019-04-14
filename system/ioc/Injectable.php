<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:22
 */

namespace Akari\system\ioc;



/**
 * Class Injectable
 * @package Akari\system\ioc
 *
 * @property \Akari\system\http\Request $request
 * @property \Akari\system\http\Response $response
 * @property \Akari\system\router\Router $router
 * @property \Akari\system\router\Dispatcher $dispatcher
 * @property \Akari\system\view\View $view
 * @property \Akari\system\http\Cookie $cookie
 * @property \Akari\system\util\\I18n $lang
 * @property \Akari\system\result\Processor $processor
 * @property \Akari\system\view\assets\AssetsManager $assets
 * @property \Akari\system\router\UrlGenerator $url
 * @property \Akari\system\util\I18n $lang
 */
class Injectable {

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
