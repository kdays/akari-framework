<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/2
 * Time: 上午8:32
 */

namespace Akari\system\tpl;


use Akari\config\ConfigItem;
use Akari\Context;
use Akari\system\ioc\DIHelper;
use Akari\system\router\Dispatcher;
use Akari\system\tpl\TemplateNotFound;

class ViewHelper {

    use DIHelper;
    
    public static $layout;
    public static $screen;
    
    public static $layoutDir = NULL;
    public static $screenDir = NULL;

    public static function setLayout($layoutName) {
        self::$layout = $layoutName;
    }

    public static function setScreen($screeName) {
        self::$screen = $screeName;
    }
    
    public static function setLayoutDir($layoutDir) {
        self::$layoutDir = $layoutDir;
    }
    
    public static function setScreenDir($screenDir) {
        self::$screenDir = $screenDir;
    }
    
    protected static function getScreenName() {
        $screenName = str_replace('.php', '', trim(Context::$appEntryName));
        // Action名称应该保证被处理..
        $screenName = explode(DIRECTORY_SEPARATOR, $screenName);
        foreach ($screenName as &$name) {
            if (substr($name, -6) == 'Action') {
                $name[0] = strtolower($name[0]);
                $name = substr($name, 0, strlen($name) - 6);
            } else {
                $name = strtolower($name);
            }
        }

        $screenName = implode(DIRECTORY_SEPARATOR, $screenName);
        $contMethod = Context::$appEntryMethod;

        if ($contMethod !== NULL) {
            if (substr($contMethod, -6) == 'Action') {
                $contMethod = substr($contMethod, 0, strlen($contMethod) - 6);
            }
            $screenName .= DIRECTORY_SEPARATOR . $contMethod;
        }

        return $screenName;
    }

    public static function getScreen() {
        $screenName = empty(self::$screen) ? self::getScreenName() : self::$screen;
        $suffix = Context::$appConfig->templateSuffix;
        
        $baseDirs = TemplateHelper::getBaseDirs(TemplateHelper::TYPE_SCREEN);
        
        /** @var Dispatcher $dispatcher */
        $dispatcher = self::_getDI()->getShared('dispatcher');
        $screenPath = NULL;
        
        foreach ($baseDirs as $screenDir) {
            $screenPath = $dispatcher->findWay($screenName, $screenDir. DIRECTORY_SEPARATOR, $suffix);
            if ($screenPath) break;
        }
            
        return $screenPath;
    }

    public static function getLayout() {
        $screenName = empty(self::$layout) ? self::getScreenName() : self::$layout;
        $suffix = Context::$appConfig->templateSuffix;

        $baseDirs = TemplateHelper::getBaseDirs(TemplateHelper::TYPE_LAYOUT);

        /** @var Dispatcher $dispatcher */
        $dispatcher = self::_getDI()->getShared('dispatcher');
        $layoutPath = NULL;

        foreach ($baseDirs as $layoutDir) {
            $layoutPath = $dispatcher->findWay($screenName, $layoutDir. DIRECTORY_SEPARATOR, $suffix);
            if ($layoutPath) break;
        }
        
        return $layoutPath;
    }

}