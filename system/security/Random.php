<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 17/3/18
 * Time: 下午10:31
 */

namespace Akari\system\security;


class Random {
    
    public static function hex($length) {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return substr(str_shuffle(str_repeat($pool, $length)), 0, $length);
    }
    
    public static function uuid($prefix = '') {
        $str = md5(uniqid(mt_rand(), true));
        $uuid  = substr($str,0,8) . '-';
        $uuid .= substr($str,8,4) . '-';
        $uuid .= substr($str,12,4) . '-';
        $uuid .= substr($str,16,4) . '-';
        $uuid .= substr($str,20,12);
        return $prefix . $uuid;
    }

    public static function number($length) {
        return rand(0, $length);
    }
    
}