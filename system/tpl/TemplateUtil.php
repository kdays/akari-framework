<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/24
 * Time: 下午6:53
 */

namespace Akari\system\tpl;

use Akari\Context;
use Akari\NotFoundClass;
use Akari\utility\Pagination;
use Akari\system\ioc\DIHelper;
use Akari\system\result\Widget;
use Akari\system\security\Security;
use Akari\system\router\UrlGenerator;
use Akari\system\tpl\asset\AssetsMgr;
use Akari\utility\ApplicationDataMgr;

class TemplateUtil {

    use DIHelper;

    public static function get($var, $defaultValue = NULL, $tplValue = '%s') {
        return empty($var) ? $defaultValue : sprintf($tplValue, $var);
    }

    public static function data($key) {
        return ApplicationDataMgr::get($key);
    }

    public static function csrf_token() {
        return Security::getCSRFToken();
    }

    public static function csrf_form() {
        if (empty(Context::$appConfig->csrfTokenName)) {
            return "";
        }

        return sprintf('<input type="hidden" name="%s" value="%s" />',
            Context::$appConfig->csrfTokenName,
            Security::getCSRFToken()
        );
    }

    public static function url($path, $arr = [], $withToken = FALSE) {
        /** @var UrlGenerator $url */
        $url = self::_getDI()->getShared("url");

        return $url->get($path, $arr, $withToken);
    }

    public static function form($url, $urlParameters = [], $method = 'POST', $formParameters = []) {
        $url = self::url($url, $urlParameters);
        $extraForm = '';
        $afterForm = '';

        $method = strtoupper($method);

        switch ($method) {
            case 'FILE':
            case 'POST':
                if ($method == 'FILE')  $extraForm = ' enctype="multipart/form-data"';
                $method = 'POST';

                if (Context::$appConfig->csrfTokenName) {
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

    public static function load_block($blockName, $params = [], $useRelative = TRUE) {
        /** @var View $view */
        $view = self::_getDI()->getShared('view');

        if ($useRelative) {
            $tplPath = View::find($blockName, View::TYPE_BLOCK);
        } else {
            $tplPath = $blockName;
        }

        $bindVars = array_merge($view->getVar(NULL), $params);
        $c = $view->getViewEngine($tplPath)->parse($tplPath, $bindVars, View::TYPE_BLOCK, FALSE);

        return $c;
    }

    public static function load_widget($widgetName, $args = []) {
        /** @var View $view */
        $view = self::_getDI()->getShared('view');

        $lists = explode(".", $widgetName);
        $len = count($lists) - 1;
        $lists[$len][0] =  strtoupper($lists[$len][0]);
        $widgetCls = implode(NAMESPACE_SEPARATOR, array_merge([Context::$appBaseNS, 'widget'], $lists));

        $lists[$len][0] =  strtolower($lists[$len][0]);
        $widgetTpl = implode(DIRECTORY_SEPARATOR, $lists);

        try {
            /** @var Widget $cls */
            $cls = new $widgetCls();
        } catch (NotFoundClass $e) {
            throw new ViewException("Not found Widget execute class: $widgetName");
        }

        $result = $cls->execute($args);
        if ($result === NULL) {
            return '';
        }

        $tplPath = View::find($widgetTpl, View::TYPE_WIDGET);
        if ($tplPath) {
            return $view->getViewEngine($tplPath)->parse($tplPath, $result, View::TYPE_WIDGET);
        }

        throw new ViewException("Not found Widget view: " . $widgetName);
    }

    public static function output_js($name = 'default') {
        /** @var AssetsMgr $assets */
        $assets = self::_getDI()->getShared('assets');

        return $assets->outputJs($name);
    }

    public static function output_css($name = 'default') {
        /** @var AssetsMgr $assets */
        $assets = self::_getDI()->getShared('assets');

        return $assets->outputCss($name);
    }

    public static function pager($pagination) {
        if (!($pagination instanceof Pagination)) {
            throw new ViewException("Template Command 'pager' need Pagination instance");
        }

        return $pagination->render();
    }

    public static function __callStatic($name, array $arguments) {
        $name[0] = strtoupper($name[0]);
        if (isset(self::$_registeredFn[$name])) {
            return call_user_func_array(self::$_registeredFn[$name], $arguments);
        }

        throw new TemplateCommandInvalid($name, $arguments);
    }

    protected static $_registeredFn = [];
    public static function registerFunction($name, callable $fn) {
        $name[0] = strtoupper($name[0]);

        self::$_registeredFn[$name] = $fn;
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
