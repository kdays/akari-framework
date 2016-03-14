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
    
    public static function getAssignValues() {
        return self::$assignKeys;
    }
    
    public static function getBaseDirs($type) {
        $baseDirs = [];

        $baseTplDir = Context::env(ConfigItem::BASE_TPL_DIR, NULL, 'template'. DIRECTORY_SEPARATOR);
        $dirPrefix = Context::env(ConfigItem::TEMPLATE_PREFIX);
        
        if ($type == self::TYPE_SCREEN && !empty(ViewHelper::$screenDir)) {
            $baseDirs[] = $baseTplDir. ViewHelper::$screenDir;
        } elseif ($type == self::TYPE_LAYOUT && !empty(ViewHelper::$layoutDir)) {
            $baseDirs[] = $baseTplDir. ViewHelper::$layoutDir;
        }

        if ($dirPrefix) {
            $baseDirs[] =  $baseTplDir. $type. DIRECTORY_SEPARATOR. $dirPrefix. DIRECTORY_SEPARATOR;
        }

        $baseDirs[] =  $baseTplDir. $type. DIRECTORY_SEPARATOR;
        
        return $baseDirs;
    }

    public static function find($tplName, $type) {
        $baseDirs = self::getBaseDirs($type);

        $suffix = Context::$appConfig->templateSuffix;
        foreach ($baseDirs as $baseDir) {
            $baseDir = Context::$appEntryPath. $baseDir;
            
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
        if (file_exists($layoutName)) {
            $this->layoutPath = $layoutName;
        } else {
            $this->layoutPath = $this->find($layoutName, self::TYPE_LAYOUT); 
        }
    }

    public function setScreen($screenName) {
        if (file_exists($screenName)) {
            $this->screenPath = $screenName;
        } else {
            $this->screenPath = $this->find($screenName, self::TYPE_SCREEN);
        }
    }

    public function getResult($data) {
        $layoutResult = $screenResult = NULL;
        $layoutPath = $this->layoutPath;
        $screenPath = $this->screenPath;

        if (empty($data) && !is_array($data)) {
            $data = self::getAssignValues();
        }  

        if (empty($layoutPath) && empty($screenPath)) {
            throw new TemplateCommandInvalid('getResult', 'templateHelper');
        }
        
        if ($layoutPath) {
            $layoutResult = $this->engine->parse($layoutPath, $data, self::TYPE_LAYOUT);
        }

        if ($screenPath) {
            $screenResult = $this->engine->parse($screenPath, $data, self::TYPE_SCREEN);
        }
        
        $result = $this->engine->getResult($layoutResult, $screenResult);
        
        return $result;
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
