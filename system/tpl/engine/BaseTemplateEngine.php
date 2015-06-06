<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午12:51
 */

namespace Akari\system\tpl\engine;


use Akari\Context;

abstract class BaseTemplateEngine {

    public $engineArgs = [];

    abstract public function parse($tplPath, array $data, $type, $onlyCompile = false);
    abstract public function getResult($layoutResult, $screenResult);

    public function getSecurityHash($tplPath, $content) {
        return "<!--@". implode(":", [
            basename($tplPath, Context::$appConfig->templateSuffix),
            md5($content),
            TIMESTAMP]). "-->";
    }

    public function getCachePath($tplPath) {
        $tpl = str_replace(Context::$appEntryPath, '', $tplPath);
        $tpl = str_replace([ '.', '/'],  '_', $tpl);

        return realpath(Context::$appBasePath. Context::$appConfig->templateCacheDir). '/'. $tpl . ".php";
    }

    public static function _getView($viewContent, array $data) {
        $view = function($pView, $assignData) {
            ob_start();
            @extract($assignData, EXTR_PREFIX_SAME, 'a_');
            include($pView);
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        };

        return $view($viewContent, $data);
    }

}