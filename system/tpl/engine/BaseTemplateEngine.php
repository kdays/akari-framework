<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午12:51
 */

namespace Akari\system\tpl\engine;

use Akari\Context;
use Akari\system\tpl\View;

abstract class BaseTemplateEngine {

    public $engineArgs = [];
    public $options = [];

    abstract public function parse($tplPath, array $data, $type, $onlyCompile = FALSE);
    abstract public function getResult($layoutResult, $screenResult);

    public function getOption($key, $defaultValue = NULL) {
        return isset($this->options[$key]) ? $this->options[$key] : $defaultValue;
    }

    public function setOption($key, $value) {
        $this->options[$key] = $value;

        return $this;
    }

    public function getCacheDir() {
        return Context::$appBasePath . DIRECTORY_SEPARATOR . Context::$appConfig->templateCacheDir . '/';
    }

    public function getCachePath($tplPath) {
        $tpl = str_replace(Context::$appEntryPath, '', $tplPath);
        $tpl = str_replace([ '.', '/', DIRECTORY_SEPARATOR], '_', $tpl);

        $cachePath = Context::$appBasePath . DIRECTORY_SEPARATOR . Context::$appConfig->templateCacheDir . '/' . $tpl . ".php";

        return $cachePath;
    }

    public static function _getView($viewContent, array $data) {
        return View::render4Data($viewContent, $data);
    }

}
