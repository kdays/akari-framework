<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:07
 */

namespace Akari\system\security\cipher;

/**
 * Warning: 这不是一个加密方式
 *
 * @package Akari\system\security\cipher
 */
Class Base64Cipher extends Cipher {

    public static function getInstance($mode = 'default'){
        return self::_instance($mode);
    }

    public function encrypt($str){
        return base64_encode($str);
    }

    public function decrypt($str){
        return base64_decode($str);
    }

}