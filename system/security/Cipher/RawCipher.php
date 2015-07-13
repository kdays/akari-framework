<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/1
 * Time: 下午11:47
 */

namespace Akari\system\security\cipher;

/**
 * Warning: 这不是一个加密方式
 *
 * @package Akari\system\security\cipher
 */
class RawCipher extends Cipher {

    public static function getInstance($mode = 'default'){
        return self::_instance($mode);
    }

    public function encrypt($str){
        return $str;
    }

    public function decrypt($str){
        return $str;
    }


}