<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/2
 * Time: 上午8:44
 */

namespace Akari\utility\helper;


use Akari\system\tpl\ViewHelper;

trait TemplateViewHelper {

    public static function _setLayout($layoutName) {
        ViewHelper::setLayout($layoutName);
    }

    public static function _setScreen($screenName) {
        ViewHelper::setScreen($screenName);
    }

}