<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午3:06
 */

namespace Akari\system\tpl;

use Akari\Context;
use Akari\NotFoundClass;
use Akari\system\ioc\DI;
use Akari\system\result\Widget;
use Akari\system\tpl\engine\BaseTemplateEngine;
use Akari\system\tpl\mod\BaseTemplateMod;

class TemplateCommand {

    public static function panelAction(BaseTemplateEngine $engine, $panelName) {
        $tplPath = TemplateHelper::find($panelName, TemplateHelper::TYPE_BLOCK);
        $cachePath = $engine->parse($tplPath, [], TemplateHelper::TYPE_BLOCK, True);
        return sprintf('<?php include(\Akari\system\tpl\TemplateCommand::loadBlock("%s", "%s"))?>', $cachePath, $tplPath);
    }

    public static function widgetAction(BaseTemplateEngine $engine, $widgetName, $args) {
        $tplPath = TemplateHelper::find(str_replace('.', DIRECTORY_SEPARATOR, $widgetName), TemplateHelper::TYPE_WIDGET);
        $cachePath = $engine->parse($tplPath, [], TemplateHelper::TYPE_WIDGET, True);
        return sprintf('<?=\Akari\system\tpl\TemplateCommand::loadWidget("%s", "%s", "%s")?>', $cachePath, $widgetName, $args);
    }

    public static function moduleAction(BaseTemplateEngine $engine, $modName, $args) {
        return sprintf('<?=\Akari\system\tpl\TemplateCommand::loadMod("%s", "%s")?>', $modName, $args);
    }

    public static function loadBlock($cachePath, $templatePath) {
        if (filemtime($cachePath) < filemtime($templatePath)) {
            $viewEngine = DI::getDefault()->getShared('viewEngine');
            $viewEngine->parse($templatePath, [], TemplateHelper::TYPE_BLOCK, True);
        }
        return $cachePath;
    }

    public static function loadWidget($cachePath, $widgetName, $args) {
        $widgetName = str_replace(".", NAMESPACE_SEPARATOR, $widgetName);
        $widgetCls = implode(NAMESPACE_SEPARATOR, [Context::$appBaseNS, "widget", $widgetName]);

        try {
            /** @var Widget $cls */
            $cls = new $widgetCls();
        } catch (NotFoundClass $e) {
            throw new TemplateNotFound("widget: $widgetName");
        }

        $result = $cls->execute($args);
        if ($result === NULL) {
            return '';
        }
        return BaseTemplateEngine::_getView($cachePath, $result);
    }

    public static function loadMod($modName, $args) {
    	$modName = ucfirst($modName);
    	
        $clsNames = [
            implode(NAMESPACE_SEPARATOR, [Context::$appBaseNS, "lib", $modName. "Mod"]),
            implode(NAMESPACE_SEPARATOR, ["Akari", "system", "tpl", "mod", $modName. "Mod"])
        ];

        $clsObj = NULL;
        foreach ($clsNames as $clsName) {
            try {
                /** @var BaseTemplateMod $clsObj */
                $clsObj = new $clsName();
                break;
            } catch (NotFoundClass $e) {
                continue;
            }
        }

        if ($clsObj === NULL) {
            throw new TemplateCommandInvalid("loadMod", $modName);
        }

        return $clsObj->run($args);
    }
}