<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/24
 * Time: 下午6:53
 */

namespace Akari\system\tpl;


use Akari\Context;
use Akari\system\ioc\DI;
use Akari\system\ioc\DIHelper;
use Akari\system\security\Security;
use Akari\system\tpl\engine\BaseTemplateEngine;
use Akari\system\tpl\mod\CsrfMod;
use Akari\utility\ResourceMgr;

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
    
    public static function url($url, $arr = [], $withToken = false) {
        if (substr($url, 0, 1) == '.') {
            // 这个时候检查Rewrite语法代表重发到特定Rewrite方法中
            if (Context::$appConfig->uriGenerator !== NULL) {
                return call_user_func_array([Context::$appConfig->uriGenerator, 'make'], [$url, $arr]);
            }
        }
        
        if ($withToken && Context::$appConfig->csrfTokenName) {
            $arr[ Context::$appConfig->csrfTokenName ] = Security::getCSRFToken();
        }
        
        return $url. (!empty($arr) ? ("?". http_build_query($arr)) : '');
    }
    
    public static function form($url, $urlParameters, $method = 'POST') {
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
                    $csrf =  new CsrfMod();
                    $afterForm = $csrf->run();
                }
                break;
            
            case 'GET':
                break;
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
    
    public static function load_block($block_name, $params = []) {
        /** @var View $view */
        $view = self::_getDI()->getShared('view');
        $tplPath = View::find($block_name, View::TYPE_BLOCK);
        
        $bindVars = array_merge($view->getVar(NULL), $params);
        $c = $view->getEngine()->parse($tplPath, $bindVars, View::TYPE_BLOCK, False);
        return $c;
    }
    
    public static function res($path) {
        ResourceMgr::push($path);
    }
}