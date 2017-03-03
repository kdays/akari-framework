<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/31
 * Time: 下午9:46
 */

namespace Akari\system\security\cipher;

/**
 * 警告 这不是一种加密!
 * 
 * Class RawCipher
 * @package Akari\system\security\cipher
 */
class RawCipher extends Cipher{

    public function encrypt($text) {
        return $text;
    }

    public function decrypt($text) {
        return $text;
    }
}
