<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/24
 * Time: 下午6:53
 */

namespace Akari\system\tpl;


use Akari\Context;
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
    
    public static function form($url, $arr, $method = 'POST') {
        $url = self::url($url, $arr);
        $extraForm = '';
        
        if (strtoupper($method) == 'FILE') {
            $method = 'POST';
            $extraForm = ' enctype="multipart/form-data"';
        }
        
        $form = '<form method="'. $method. '" action="'. $url . '"'. $extraForm .  '>'. "\n";
        if (Context::$appConfig->csrfTokenName) {
            $csrf =  new CsrfMod();
            $form .= $csrf->run();
        }
        return $form;
    }
    
    public static function end_form() {
        return '</form>';
    }
}