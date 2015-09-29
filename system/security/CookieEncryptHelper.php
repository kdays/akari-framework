<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/9/29
 * Time: 上午8:57
 */

namespace Akari\system\security;

use Akari\system\security\cipher\Cipher;

class CookieEncryptHelper extends Cipher{

    const FLAG = "|ENC";
    
    public function encrypt($str){
        return Security::encrypt($str, 'cookie'). self::FLAG;
    }

    public function decrypt($str) {
        $len = strlen(self::FLAG);
        if (substr($str, -$len, $len) == self::FLAG) {
            $str = Security::decrypt(substr($str, 0, -$len), 'cookie');
        }
        
        return $str;
    }

}