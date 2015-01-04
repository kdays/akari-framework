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

    protected static $data = [];
    protected static $loadedPackages = [];

    public function getPath($name) {
        $baseDir = implode(DIRECTORY_SEPARATOR, [
            Context::$appEntryPath, 'language', ''
        ]);

        $languageId = FALSE;
        if (C(ConfigItem::LANGUAGE_ID)) $languageId = C(ConfigItem::LANGUAGE_ID);

        if ($languageId) {
            if (file_exists($baseDir. $languageId. "/$name.php")) {
                return $baseDir. $languageId. "/$name.php";
            } elseif (file_exists( $baseDir. $languageId. ".$name.php" )) {
                return $baseDir. $languageId. ".$name.php";
            }
        }

        if (file_exists($baseDir. "$name.php")) {
            return $baseDir. "$name.php";
        }

        return FALSE;
    }

    public static function loadPackage($packageId, $prefix = "") {
        if (isset(self::$loadedPackages[ $prefix. $packageId ])) return FALSE;

        $path = self::getPath($packageId);
        if (!$path) {
            throw new LanguageNotFound($packageId);
        }

        self::$loadedPackages[ $prefix. $packageId ] = true;

        $now = require($path);
        foreach ($now as $key => $value) {
            self::$data[ $prefix. $key ] = $value;
        }
    }

    public static function get($id, $L = [], $prefix = "") {
        $id = $prefix.$id;
        $lang = isset(self::$data[$id]) ? self::$data[$id] : "[$id]";

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