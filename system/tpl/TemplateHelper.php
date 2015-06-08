<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午12:50
 */

namespace Akari\system\tpl;


use Akari\config\ConfigItem;
use Akari\Context;
use Akari\system\ioc\DI;
use Akari\system\tpl\engine\BaseTemplateEngine;

class TemplateHelper {

    const TYPE_BLOCK = 'block';
    const TYPE_SCREEN = 'view';
    const TYPE_LAYOUT = 'layout';
    const TYPE_WIDGET = 'widget';

    /** @var BaseTemplateEngine */
    protected $engine;

    protected $layoutPath;
    protected $screenPath;

    public function __construct() {
        $this->engine = DI::getDefault()->getShared('viewEngine');
    }

    protected static $assignKeys = [];
    public static function assign($key, $value) {
        if (is_array($key)) {
            self::$assignKeys += $key;
        } else {
            self::$assignKeys[$key] = $value;
        }
    }

    public static function find($tplName, $type) {

        $suffix = Context::$appConfig->templateSuffix;
        $baseDirs = [];

        if (C(ConfigItem::BASE_TPL_DIR)) {
            $baseDirs[] = C(ConfigItem::BASE_TPL_DIR). DIRECTORY_SEPARATOR. $type. DIRECTORY_SEPARATOR;
        }

        $baseDirs[] = Context::$appEntryPath. "template". DIRECTORY_SEPARATOR. $type. DIRECTORY_SEPARATOR;

        foreach ($baseDirs as $baseDir) {
            if (file_exists($tplPath = $baseDir. $tplName. $suffix)) {
                return realpath($tplPath);
            }

            if (file_exists($tplPath = $baseDir. "default". $suffix)) {
                return realpath($tplPath);
            }
        }

        throw new TemplateNotFound($type. " -> ". $tplName);
    }

    public function setLayout($layoutName) {
        $this->layoutPath = $this->find($layoutName, self::TYPE_LAYOUT);
    }

    public function setScreen($screenName) {
        $this->screenPath = $this->find($screenName, self::TYPE_SCREEN);
    }

    public function getResult($data) {
        $layout = $this->layoutPath;
        $screen = $this->screenPath;

        if ($data == NULL) {
            $data = self::$assignKeys;
        }

        if (empty($layout) && empty($screen)) {
            throw new TemplateCommandInvalid('getResult', 'templateHelper');
        }

        if ($layout) {
            $layout = $this->engine->parse($layout, $data, self::TYPE_LAYOUT);
        }

        if ($screen) {
            $screen = $this->engine->parse($screen, $data, self::TYPE_SCREEN);
        }


        return $this->engine->getResult($layout, $screen);
    }
}


Class TemplateNotFound extends \Exception {

    public function __construct($template) {
        $this->message = sprintf("Not Found Template [ %s ]", $template);
    }

}


Class TemplateCommandInvalid extends \Exception {

    public function __construct($commandName, $args, $file = NULL) {
        $file = str_replace(Context::$appEntryPath, '', $file);
        $this->message = sprintf("Template Command Invalid: [ %s ] with [ %s ] on [ %s ]", $commandName, $args, $file);
    }

}
