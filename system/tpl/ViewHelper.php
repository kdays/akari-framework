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
            $screenName .= DIRECTORY_SEPARATOR. $contMethod;
        }
        
        return $screenName;
    }

    public static function getScreen() {
        $screenName = self::getScreenName();
        $screenPath = self::$screen;
        $suffix = Context::$appConfig->templateSuffix;
        
        if ($screenPath == NULL) {
            /** @var Dispatcher $dispatcher */
            $dispatcher = self::_getDI()->getShared('dispatcher');
            
            $prefix = Context::env(ConfigItem::TEMPLATE_PREFIX);
            if ($prefix) {
                $screenPath = $dispatcher->findWay($screenName, 'template/view/'.$prefix. "/", $suffix);
                if ($screenPath) {
                    $screenPath = str_replace([Context::$appEntryPath, $suffix, '/template/view/'. $prefix. "/"], '', $screenPath);
                }
            }
            
            if (!$screenPath) {
                $screenPath = $dispatcher->findWay($screenName, 'template/view/', $suffix);
                $screenPath = str_replace([Context::$appEntryPath, $suffix, '/template/view/'], '', $screenPath);
            }

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
            /** @var Dispatcher $dispatcher */
            $dispatcher = self::_getDI()->getShared('dispatcher');
            
            $layoutPath = $dispatcher->findWay($screenName, 'template/layout/', $suffix);
            $layoutPath = str_replace([Context::$appEntryPath, $suffix, '/template/layout/'], '', $layoutPath);
        }
        
        return $layoutPath;
    }

}