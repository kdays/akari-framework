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
use Akari\system\ioc\DIHelper;
use Akari\system\result\Widget;
use Akari\system\security\Security;
use Akari\system\tpl\asset\AssetsMgr;
use Akari\system\tpl\engine\BaseTemplateEngine;
use Akari\utility\PageHelper;
use Akari\utility\UrlHelper;

class TemplateUtil {
    
    use DIHelper;
    
    public static function get($var, $defaultValue = NULL, $tplValue = '%s') {
        return empty($var) ? $defaultValue : sprintf($tplValue, $var);
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
    
    public static function url($path, $arr = [], $withToken = false) {
        /** @var UrlHelper $url */
        $url = self::_getDI()->getShared("url");
        return $url->get($path, $arr, $withToken);
    }
    
    public static function form($url, $urlParameters, $method = 'POST', $formParameters = []) {
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
        ,$method, $url, $extraForm, $afterForm
);
    }
    
    public static function end_form() {
        return '</form>';
    }
    
    public static function load_block($blockName, $params = []) {
        /** @var View $view */
        $view = self::_getDI()->getShared('view');
        $tplPath = View::find($blockName, View::TYPE_BLOCK);
        
        $bindVars = array_merge($view->getVar(NULL), $params);
        $c = $view->getEngine()->parse($tplPath, $bindVars, View::TYPE_BLOCK, False);
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
            throw new TemplateNotFound("widget class: $widgetName");
        }

        // 模板只需要编译后在讲result处理
        $tplPath = View::find($widgetTpl, View::TYPE_WIDGET);
        $cachePath = $view->getEngine()->parse($tplPath, [], View::TYPE_WIDGET, True);
        
        $result = $cls->execute($args);
        if ($result === NULL) {
            return '';
        }
        return BaseTemplateEngine::_getView($cachePath, $result);
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
    
    public static function pager($instanceName = 'default') {
        return PageHelper::getInstance($instanceName)->getHTML();
    }
    
    public static function __callStatic($name, $arguments) {
        $name[0] = strtoupper($name[0]);
        
        $utilCls = implode(NAMESPACE_SEPARATOR, [
            Context::$appBaseNS, 'lib', $name. 'Plugin'
        ]);
        
        try {
            if (class_exists($utilCls) && method_exists($utilCls, 'execute')) {
                return $utilCls::execute($arguments);
            }
        } catch (NotFoundClass $e) {
            
        }
        
        throw new TemplateCommandInvalid($name, $arguments);
    }
}