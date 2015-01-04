<?php
namespace Akari\utility;

use Akari\Context;

Class DataHelper{
    public static $data = array();

    /**
     * @param bool|string $key
     * @param bool|string $subKey
     * @param mixed $defaultValue
     * @return array|null|object
     */
    public static function get($key = false, $subKey = false, $defaultValue = NULL){
        if($key === false){
            return self::$data;
        }else{
            if(!isset(self::$data[$key]))	return $defaultValue;
            if($subKey){
                if (is_array(self::$data[$key])) {
                    return array_key_exists($subKey, self::$data[$key]) ? self::$data[$key][$subKey] : $defaultValue;
                } elseif (is_object(self::$data[$key])) {
                    return isset(self::$data[$key]->$subKey) ?  self::$data[$key]->$subKey : $defaultValue;
                }
            }

            return self::$data[$key];
        }
    }

    public static function set($key = '', $data = array(), $isOverwrite = true){
        if(!isset(self::$data[$key])){
            self::$data[$key] = $data;
        }else{
            if($isOverwrite)	self::$data[$key] = $data;
        }

        return TRUE;
    }

    public static function update($key, $s_key, $value){
        if(!isset(self::$data[$key]))	self::$data[$key] = array();
        self::$data[$key][$s_key] = $value;
    }

    public static function load($path, $key = ''){
        if($key == '')	$key = array_shift(explode(".", basename($path)));

        $sPath = Context::$appBasePath."data/$path";
        if(!file_exists($sPath))	return false;
        if(preg_match('/\.php/i', $path)){
            self::set($key, self::GetFile($sPath));
        }

        return true;
    }

    public static function GetFile($path){
        return include($path);
    }
}