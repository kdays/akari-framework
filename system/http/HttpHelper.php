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

}