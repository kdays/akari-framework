<?php
namespace Akari\model;

use ReflectionClass;

!defined("AKARI_PATH") && exit;

Class CodeModel extends Model{

    public static function get($key = FALSE) {
        $reflect = new ReflectionClass(get_called_class());

        if ($key) {
            return $reflect->getConstant($key);
        }
        return $reflect->getConstants();
    }

}