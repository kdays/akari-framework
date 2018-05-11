<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 17/1/25
 * Time: 下午5:00
 */

namespace Akari\system\i18n;

use Akari\Context;
use Akari\config\ConfigItem;

class I18n {

    protected $_data = [];
    protected $loadedPackages = [];

    protected function getPath($name) {
        $suffix = ".php";
        $baseDir = Context::$appEntryPath . DIRECTORY_SEPARATOR . "language" . DIRECTORY_SEPARATOR;
        $languageId = Context::env(ConfigItem::LANGUAGE_ID, NULL, FALSE);

        $paths = [];
        if ($languageId) {
            $paths[] = $baseDir . $languageId . DIRECTORY_SEPARATOR . $name . $suffix;
            $paths[] = $baseDir . $languageId . "." . $name . $suffix;
        }

        $paths[] = $baseDir . $name . $suffix;

        foreach ($paths as $path) {
            if (file_exists($path)) return $path;
        }

        return FALSE;
    }

    public function loadPackage($packageId, $prefix = "", $useRelative = TRUE) {
        if (isset($this->loadedPackages[ $prefix . $packageId ])) {
            return FALSE;
        }

        $path = $useRelative ? $this->getPath($packageId) : $packageId;
        if ((!$path && $useRelative) || (!$useRelative && !file_exists($path))) {
            throw new LanguageNotFound($packageId);
        }

        $this->loadedPackages[ $prefix . $packageId ] = TRUE;

        $allData = require $path;
        foreach ($allData as $key => $value) {
            $this->_data[ $prefix . $key ] = $value;
        }
    }

    public function has($id, $prefix = "") {
        $id = $prefix . $id;

        return array_key_exists($id, $this->_data);
    }

    public function get($id, $L = [], $prefix = "") {
        if (!$this->has($id, $prefix)) {
            return $id;
        }

        $lang = $this->_data[ $prefix . $id ];
        foreach($L as $key => $value){
            if (is_string($value) || is_numeric($value)) {
                $lang = str_replace("%$key%", $value, $lang);
            }
        }

        // 处理![语言句子] 或被替换成L(语言句子)
        $that = $this;
        $lang = preg_replace_callback('/\!\[(\S+)\]/i', function ($matches) use ($that) {
            if (isset($matches[1])) {
                return $that->get($matches[1]);
            }

            return $matches[0];
        }, $lang);

        return $lang;
    }

    public function match($id, $defaultValue = NULL, $L = []) {
        if ($this->has($id)) {
            return $this->get($id, $L);
        }

        return $defaultValue === NULL ? $id : $defaultValue;
    }

}
