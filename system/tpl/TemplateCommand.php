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
use Akari\system\ioc\DIHelper;
use Akari\system\result\Widget;
use Akari\system\tpl\engine\BaseTemplateEngine;
use Akari\system\tpl\mod\BaseTemplateMod;

class TemplateCommand{

    use DIHelper;
    
    public static function widgetAction(BaseTemplateEngine $engine, $widgetName, $args = "", $direct = False) {
        $tplPath = View::find(str_replace('.', DIRECTORY_SEPARATOR, $widgetName), View::TYPE_WIDGET);
        $cachePath = $engine->parse($tplPath, [], View::TYPE_WIDGET, True);

        if ($direct) {
            return self::loadWidget($cachePath, $widgetName, $args);
        }
        return sprintf('<?=\Akari\system\tpl\TemplateCommand::loadWidget("%s", "%s", "%s")?>', $cachePath, $widgetName, $args);
    }

    public static function moduleAction(BaseTemplateEngine $engine, $modName, $args) {
        if (strpos($modName, ' ') !== FALSE) {
            $args = explode(" ", $modName);
            $modName = array_shift($args);
        }

        return sprintf('<?=\Akari\system\tpl\TemplateCommand::loadMod("%s", "%s")?>', $modName, is_array($args) ? implode(" ", $args) : '');
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

        return empty($args) ? $clsObj->run() : $clsObj->run($args);
    }
}