<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:06
 */

namespace Akari\utility\helper;

trait MobileHelper {

    public static function _isMobile() {
        if (self::_isIPhone()) {
            return True;
        }

        $_userAgent = $_SERVER['HTTP_USER_AGENT'];
        $keyword = ["ucweb", "Windows Phone", "android", "opera mini", "blackberry"];
        foreach ($keyword as $value) {
            if (preg_match("/$value/i", $_userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 是否是iphone
     * @return bool
     */
    public static function _isIPhone(){
        $_userAgent = $_SERVER['HTTP_USER_AGENT'];

        return (preg_match('/ipod/i', $_userAgent) ||
            preg_match('/iphone/i', $_userAgent));
    }

    /**
     * 判断是否是微信的
     * @return bool
     */
    public static function _isWX() {
        $_userAgent = $_SERVER['HTTP_USER_AGENT'];

        return (preg_match('/MicroMessenger/i', $_userAgent) ||
            preg_match('/Window Phone/i', $_userAgent));
    }

}