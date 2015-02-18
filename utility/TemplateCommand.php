<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 19:19
 */

namespace Akari\utility;

use Akari\Context;
use Akari\NotFoundClass;

Class TemplateCommand {

    public static $screen = "";

    public static function getScreen() {
        echo self::$screen;
    }

    public static function panel($panelName, $args = []) {
        $panelPath = implode(DIRECTORY_SEPARATOR, [
            Context::$appEntryPath, "template", "panel",
            $panelName. Context::$appConfig->templateSuffix
        ]);

        if (!file_exists($panelPath)) {
            throw new TemplateNotFound("panel:". $panelName);
        }
        $view = function($path, $data) {
            ob_start();
            @extract($data, EXTR_PREFIX_SAME, 'a_');
            include($path);
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        };

        echo $view($panelPath, array_merge(TemplateHelper::getInstance()->assign(NULL, NULL), $args));
    }

    public static function module($id, $data = '') {
        $id = ucfirst($id);

        $appCls = Context::$appBaseNS."\\lib\\{$id}Module";
        $sysCls = implode(NAMESPACE_SEPARATOR, ["Akari", "utility", "template", $id]);

        try {
            if (class_exists($appCls)) {
                $clsObj = new $appCls();
            }
        } catch (NotFoundClass $e) {
            try {
                if (class_exists($sysCls)) {
                    $clsObj = new $sysCls();
                }
            } catch (NotFoundClass $ex) {
                throw new TemplateModuleNotFound($id);
            }
        }

        if ($data) {
            return $clsObj->run($data);
        }
        return $clsObj->run();
    }

    public static function widget($widgetName) {
        $widgetAppPath = implode(DIRECTORY_SEPARATOR, [
            Context::$appEntryPath, "widget", $widgetName.".php"
        ]);

        $widgetTemplatePath = implode(DIRECTORY_SEPARATOR, [
            Context::$appEntryPath, "template", "layout", $widgetName. Context::$appConfig->templateSuffix
        ]);

        if (!file_exists($widgetAppPath)) {
            throw new TemplateNotFound("widget app: $widgetName");
        }

        if (!file_exists($widgetTemplatePath)) {
            throw new TemplateNotFound("widget: $widgetName");
        }

        $widgetResult = require($widgetAppPath);
        $view = function($path, $data) {
            ob_start();
            @extract($data, EXTR_PREFIX_SAME, 'a_');
            include($path);
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        };

        if ($widgetResult !== FALSE) {
            echo $view($widgetTemplatePath, $widgetResult);
        }
    }

}

Class TemplateModuleNotFound extends \Exception {

}