<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/2
 * Time: 上午8:32
 */

namespace Akari\system\tpl;


use Akari\Context;
use Akari\system\router\Dispatcher;
use Akari\system\tpl\TemplateNotFound;

class ViewHelper {

    protected static $layout;
    protected static $screen;

    public static function setLayout($layoutName) {
        self::$layout = $layoutName;
    }

    public static function setScreen($screeName) {
        self::$screen = $screeName;
    }

    protected static function getScreenName() {
        $screenName = str_replace('.php', '', trim(Context::$appEntryName));
        if (substr($screenName, -6) == 'Action') {
            $screenName = substr($screenName, 0, strlen($screenName) - 6);
        }
        $contMethod = Context::$appEntryMethod;
        
        if ($contMethod !== NULL) {
            if (substr($contMethod, -6) == 'Action') {
                $contMethod = substr($contMethod, 0, strlen($contMethod) - 6);
            }
            $screenName .= DIRECTORY_SEPARATOR. $contMethod;
        }
        
        return $screenName;
    }

    public static function getScreen() {
        $screenName = self::getScreenName();
        $screenPath = self::$screen;
        $suffix = Context::$appConfig->templateSuffix;
        
        if ($screenPath == NULL) {
            $screenPath = Dispatcher::getInstance()->findWay($screenName, 'template/view/', $suffix);
            $screenPath = str_replace([Context::$appEntryPath, $suffix, '/template/view/'], '', $screenPath);

            if ($screenPath == '') {
                throw new TemplateNotFound('screen Default');
            }
        }

        return $screenPath;
    }

    public static function getLayout() {
        $screenName = self::getScreenName();
        $layoutPath = self::$layout;
        $suffix = Context::$appConfig->templateSuffix;

        if ($layoutPath == NULL) {
            $layoutPath = Dispatcher::getInstance()->findWay($screenName, 'template/layout/', $suffix);
            $layoutPath = str_replace([Context::$appEntryPath, $suffix, '/template/layout/'], '', $layoutPath);
        }

        return $layoutPath;
    }

}