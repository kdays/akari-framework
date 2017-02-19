<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/4/13
 * Time: 下午12:47.
 */

namespace Akari\system\security;

use Akari\system\security\cipher\Cipher;

class DefaultCookieEncrypt extends Cipher
{
    const FLAG = '|ENC';

    public function encrypt($str)
    {
        return Security::encrypt($str, 'cookie').self::FLAG;
    }

    public function decrypt($str)
    {
        $len = strlen(self::FLAG);
        if (substr($str, -$len, $len) == self::FLAG) {
            $str = Security::decrypt(substr($str, 0, -$len), 'cookie');
        }

        return $str;
    }
}
