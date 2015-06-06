<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/1/26
 * Time: 下午9:01
 */

namespace Akari\system\http;


trait HttpHelper {

    public static function _isXhr() {
        //HTTP_X_REQUESTED_WITH jquery  HTTP_SEND_BY  minatojs
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_SERVER['HTTP_SEND_BY']);
    }

    public static function _isPost() {
        return $_SERVER['REQUEST_METHOD'] == 'POST';
    }

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