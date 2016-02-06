<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:10
 */

namespace Akari\model;

use ReflectionClass;

!defined("AKARI_PATH") && exit;

abstract Class CodeModel extends Model{

    public static function get($key = FALSE) {
        $reflect = new ReflectionClass(get_called_class());

        if ($key) {
            return $reflect->getConstant($key);
        }
        return $reflect->getConstants();
    }

}