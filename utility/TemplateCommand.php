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
use Akari\system\result\Widget;
use Akari\utility\template\BaseTemplateModule;

Class TemplateCommand {

    public static $screen = "";

    public static function getScreen() {
        echo self::$screen;
    }

    public static function panel($panelName, $args = []) {
        $panelPath = implode(DIRECTORY_SEPARATOR, [
            TemplateHelper::getInstance()->basePath, "panel",
            $panelName. Context::$appConfig->templateSuffix
        ]);

        if (!file_exists($panelPath)) {
            throw new TemplateNotFound("panel:". $panelName);
        }

        $realPanelPath = TemplateHelper::getInstance()->parseTemplate($panelPath);
        $view = function($path, $data) {
            ob_start();
            @extract($data, EXTR_PREFIX_SAME, 'a_');
            include($path);
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        };

        echo $view($realPanelPath, array_merge(TemplateHelper::getInstance()->assign(NULL, NULL), $args));
    }

    public static function lang($str, $data = '') {
        echo L($str);
    }

    public static function module($id, $data = '') {
        $id = ucfirst($id);

        $appCls = implode(NAMESPACE_SEPARATOR, [Context::$appBaseNS, "lib", "{$id}Module"]);
        $sysCls = implode(NAMESPACE_SEPARATOR, ["Akari", "utility", "template", $id]);

        try {
            /** @var BaseTemplateModule $clsObj */
            $clsObj = new $appCls();
        } catch (NotFoundClass $e) {
            try {
                $clsObj = new $sysCls();
            } catch (NotFoundClass $ex) {
                throw new TemplateModuleNotFound($id);
            }
        }

        if ($data) {
            return $clsObj->run($data);
        }
        return $clsObj->run();
    }

    public static function widget($widgetName, $userData = NULL) {
        $widgetCls = implode(NAMESPACE_SEPARATOR, [Context::$appBaseNS, "widget", $widgetName]);

        try {
            /** @var Widget $cls */
            $cls = new $widgetCls();
        } catch (NotFoundClass $e) {
            throw new TemplateNotFound("widget: $widgetName");
        }

        $widgetTemplatePath = implode(DIRECTORY_SEPARATOR, [
            TemplateHelper::getInstance()->basePath, "widget",
                $widgetName. Context::$appConfig->templateSuffix
        ]);

        if (!file_exists($widgetTemplatePath)) {
            throw new TemplateNotFound("widget: $widgetName");
        }

        $widgetResult = $cls->execute($userData);
        $realWidgetTemplatePath = TemplateHelper::getInstance()->parseTemplate($widgetTemplatePath);
        $view = function($path, $data) {
            ob_start();
            @extract($data, EXTR_PREFIX_SAME, 'a_');
            include($path);
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        };

        if ($widgetResult !== FALSE) {
            echo $view($realWidgetTemplatePath, $widgetResult);
        }
    }

}

Class TemplateModuleNotFound extends \Exception {

}