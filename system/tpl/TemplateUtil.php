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
use Akari\system\tpl\engine\BaseTemplateEngine;
use Akari\system\tpl\mod\CsrfMod;

class TemplateUtil {
    
    public static function get($var, $defaultValue = NULL, $tplValue = '%s') {
        return empty($var) ? $defaultValue : sprintf($tplValue, $var);
    }
    
    public static function url($url, $arr) {
        if (substr($url, 0, 1) == '.') {
            // 这个时候检查Rewrite语法代表重发到特定Rewrite方法中
            if (Context::$appConfig->uriGenerator !== NULL) {
                return call_user_func_array([Context::$appConfig->uriGenerator, 'make'], [$url, $arr]);
            }
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
    
    public static function load_block($block_name) {
        /** @var BaseTemplateEngine $viewEngine */
        $viewEngine = DI::getDefault()->getShared('viewEngine');
        $tplPath = TemplateHelper::find($block_name, TemplateHelper::TYPE_BLOCK);
        
        $c = $viewEngine->parse($tplPath, [], TemplateHelper::TYPE_BLOCK, False);
        return $c;
    }
}