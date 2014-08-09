<?php
namespace Akari\model;

!defined("AKARI_PATH") && exit;

Class CodeModel extends Model{
    public function getName($code){
        foreach($this as $key => $value){
            if($value == $code) return $key;
        }
        
        return FALSE;
    }

    public static $m = [];
    public static function getInstance() {
        $class = get_called_class();
        if (!isset(self::$m[$class])) {
            self::$m[ $class ] = new $class;
        }

        return self::$m[ $class ];
    }
    
    public function getCode($name, $defaultValue = FALSE){
        if($this->$name)    return $this->$name;
        return $defaultValue;
    }
    
    public function addCode($name, $code){
        $this->$name = $code;
    }
    
    public function getCodeList(){
        $list = array();
        foreach($this as $key => $value){
            $list[$key] = $value;
        }
        
        return $list;
    }
}