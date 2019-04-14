<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/27
 * Time: 0:02
 */

namespace Akari\system\view;

use Akari\Core;
use Akari\system\ioc\DI;
use Akari\system\event\Event;
use Akari\system\ioc\Injectable;
use Akari\system\util\Pagination;
use Akari\system\util\AppValueMgr;
use Akari\exception\AkariException;
use Akari\system\view\assets\AssetsManager;

class ViewFunctions extends Injectable {

    const EVENT_REGISTER_FUNC = 'view.reg_func';

    public static function get(string $var, $defaultValue = NULL, string $tplValue = '%s') {
        return empty($var) ? $defaultValue : sprintf($tplValue, $var);
    }

    public static function data(string $key) {
        return AppValueMgr::get($key);
    }

    public static function lang(string $key, array $L = [], string $prefix = '') {
        $di = DI::getDefault();

        return $di->getShared('lang')->get($key, $L, $prefix);
    }

    public static function url(string $path, array $args = [], $withToken = FALSE) {
        $di = DI::getDefault();

        return $di->getShared('url')->get($path, $args, $withToken);
    }

    public static function static_url(string $path) {
        $di = DI::getDefault();

        return $di->getShared('url')->getStaticUrl($path);
    }

    public static function form(string $url, array $urlParameters, string $method = 'POST', array $formParameters = []) {
        $url = self::url($url, $urlParameters);
        $extraForm = '';
        $afterForm = '';

        $method = strtoupper($method);

        switch ($method) {
            case 'FILE':
            case 'POST':
                if ($method == 'FILE')  $extraForm = ' enctype="multipart/form-data"';
                $method = 'POST';

                if (array_key_exists("csrf_form", self::$_registeredFn)) {
                    $afterForm .= self::csrf_form();
                }

                break;

            case 'GET':
                break;
        }

        foreach ($formParameters as $k => $v) {
            $extraForm .= "$k=\"$v\"";
        }

        return sprintf(<<<'EOT'
<form method="%s" action="%s" %s>%s
EOT
            , $method, $url, $extraForm, $afterForm
        );
    }


    public static function end_form() {
        return '</form>';
    }

    public static function load_widget(string $widgetName, array $params) {
        $di = DI::getDefault();
        /** @var View $view */
        $view = $di->getShared('view');

        $widgetCls = implode(NAMESPACE_SEPARATOR, [Core::$appNs, 'widget', ucfirst($widgetName)]);
        $widgetCls = new $widgetCls();

        $tplPath = $view->find($widgetName, View::TYPE_BLOCK);

        if ($widgetCls instanceof BaseWidget) {
            $result = $widgetCls->handle($params);

            $c = $view->getViewEngine($tplPath)->parse($tplPath, $result, View::TYPE_BLOCK, FALSE);

            return $c;
        }

        throw new AkariException(get_class($widgetCls) . " must instance of BaseWidget");
    }

    public static function load_block(string $blockName, array $params, $useRelative = TRUE) {
        $di = DI::getDefault();

        /** @var View $view */
        $view = $di->getShared('view');

        if ($useRelative) {
            $tplPath = $view->find($blockName, View::TYPE_BLOCK);
        } else {
            $tplPath = $blockName;
        }

        $bindVars = array_merge($view->getVar(NULL), $params);
        $c = $view->getViewEngine($tplPath)->parse($tplPath, $bindVars, View::TYPE_BLOCK, FALSE);

        return $c;
    }

    public static function output_js(string $name = 'default') {
        $di = DI::getDefault();

        /** @var AssetsManager $assets */
        $assets = $di->getShared('assets');

        return $assets->outputJs($name);
    }

    public static function output_css(string $name = 'default') {
        $di = DI::getDefault();

        /** @var AssetsManager $assets */
        $assets = $di->getShared('assets');

        return $assets->outputCss($name);
    }

    public static function screen() {
        $di = DI::getDefault();

        /** @var View $view */
        $view = $di->getShared('view');

        return $view->getScreenResult();
    }

    public static function pages(Pagination $pagination) {
        $bindVars = $pagination->getBindVars();

        return self::load_block($pagination->viewName, $bindVars);
    }

    public static function __callStatic($name, array $arguments) {
        if (isset(self::$_registeredFn[$name])) {
            return call_user_func_array(self::$_registeredFn[$name], $arguments);
        }

        throw new AkariException("Missing view function: " . $name);
    }

    protected static $_registeredFn = [];
    public static function registerFunction($name, callable $fn) {
        //$name[0] = strtoupper($name[0]);
        self::$_registeredFn[$name] = $fn;

        Event::fire(self::EVENT_REGISTER_FUNC, [$name]);
    }

    public static function getRegisteredFunctions($includeDefaultMethods = FALSE) {
        $methods = [];

        if ($includeDefaultMethods) {
            $defaultMethods = get_class_methods(self::class);
            $exceptMethods = [
                'registerFunction',
                'getRegisteredFunctions',
                '__callStatic'
            ];

            $methods = array_diff($defaultMethods, $exceptMethods);
        }


        return array_merge($methods, array_keys(self::$_registeredFn));
    }

}
