<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 15:24
 */

namespace Akari\utility;

use Akari\config\ConfigItem;
use Akari\Context;

Class I18n {

    protected static $_data = [];
    protected static $loadedPackages = [];

    protected static function getPath($name) {
        $baseDir = implode(DIRECTORY_SEPARATOR, [
            Context::$appEntryPath, 'language', ''
        ]);

        $languageId = Context::env(ConfigItem::LANGUAGE_ID, NULL, FALSE);
        $suffix = ".php";
        
        $paths = [];
        if ($languageId) {
            $paths[] = $baseDir. $languageId. DIRECTORY_SEPARATOR. $name. $suffix;
            $paths[] = $baseDir. $languageId. ".". $name. $suffix;
        }
        
        $paths[] = $baseDir. $name. $suffix;

        foreach ($paths as $path) {
            if (file_exists($path)) return $path;
        }
        
        return FALSE;
    }

    public static function loadPackage($packageId, $prefix = "") {
        if (isset(self::$loadedPackages[ $prefix. $packageId ])) {
            return FALSE;
        }

        $path = self::getPath($packageId);
        if (!$path) {
            throw new LanguageNotFound($packageId);
        }

        self::$loadedPackages[ $prefix. $packageId ] = true;

        $now = require($path);
        foreach ($now as $key => $value) {
            self::$_data[ $prefix. $key ] = $value;
        }
    }
    
    public static function has($id, $prefix = "") {
        $id = $prefix. $id;
        return array_key_exists($id, self::$_data);
    }

    public static function get($id, $L = [], $prefix = "") {
        if (!self::has($id, $prefix)) {
            return $id;
        }
        
        $lang = self::$_data[ $prefix. $id ];
        foreach($L as $key => $value){
            $lang = str_replace("%$key%", $value, $lang);
        }

        // 处理![语言句子] 或被替换成L(语言句子)
        $lang = preg_replace_callback('/\!\[(\S+)\]/i', function($matches){
            if (isset($matches[1])) {
                return I18n::get($matches[1]);
            }

            return $matches[0];
        }, $lang);

        return $lang;
    }

}

Class LanguageNotFound extends \Exception {

    public function __construct($packageId) {
        $this->message = "Language Package [ ". $packageId. " ] not found";
    }

}