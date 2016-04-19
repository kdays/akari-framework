<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/2
 * Time: 上午8:44
 */

namespace Akari\system\tpl;

use Akari\config\ConfigItem;
use Akari\Context;
use Akari\system\ioc\Injectable;
use Akari\system\tpl\engine\BaseTemplateEngine;

class View extends Injectable{

    const TYPE_BLOCK = 'block';
    const TYPE_SCREEN = 'view';
    const TYPE_LAYOUT = 'layout';
    const TYPE_WIDGET = 'widget';
    
    protected $layout;
    protected $screen;
    
    protected static $_layoutDir;
    protected static $_screenDir;
    
    private $_vars = [];

    /**
     * 将参数绑定到模板 别的不会执行  
     *
     * @param string|array $key
     * @param mixed $value
     * @return array
     */
    public function bindVar($key, $value = NULL) {
        if (is_array($key)) {
            $this->_vars = array_merge($key, $this->_vars);
        } else {
            $this->_vars[$key] = $value;
        }
    }
    
    public function hasVar($key) {
        return array_key_exists($key, $this->_vars);
    }
    
    public function getVar($key) {
        if ($key !== NULL) {
            return $this->hasVar($key) ? $this->_vars[$key] : NULL;
        }
        return $this->_vars;
    }
    
    public function setLayout($layoutName) {
        $this->layout = $layoutName;
    }

    public function setScreen($screenName) {
        $this->screen = $screenName;
    }
    
    public function getLayout() {
        return $this->layout;
    }
    
    public function getScreen() {
        return $this->screen;
    }
    
    public function setLayoutDir($_layoutDir) {
        self::$_layoutDir = $_layoutDir;
    }

    public function setScreenDir($_screenDir) {
        self::$_screenDir = $_screenDir;
    }
    
    public function getScreenPath() {
        $screenName = empty($this->screen) ? $this->getScreenName() : $this->screen;

        $suffix = Context::$appConfig->templateSuffix;
        $baseDirs = $this->getBaseDirs(self::TYPE_SCREEN);
            
        $screenPath = NULL;

        foreach ($baseDirs as $screenDir) {
            $screenPath = $this->dispatcher->findWay($screenName, $screenDir. DIRECTORY_SEPARATOR, $suffix);
            if ($screenPath) break;
        }

        return $screenPath;
    }
    
    public function getLayoutPath() {
        $screenName = empty($this->layout) ? $this->getScreenName() : $this->layout;
        $suffix = Context::$appConfig->templateSuffix;

        $baseDirs = $this->getBaseDirs(self::TYPE_LAYOUT);

        $layoutPath = NULL;
        foreach ($baseDirs as $layoutDir) {
            $layoutPath = $this->dispatcher->findWay($screenName, $layoutDir. DIRECTORY_SEPARATOR, $suffix);
            if ($layoutPath) break;
        }

        return $layoutPath;
    }
    
    public function setBaseViewDir($viewDir) {
        Context::env(ConfigItem::BASE_TPL_DIR, $viewDir);
    }

    protected function getScreenName() {
        $screenName = $this->dispatcher->getControllerName();
        $screenName = explode(NAMESPACE_SEPARATOR, $screenName);
        foreach ($screenName as &$name) {
            if (substr($name, -6) == 'Action') {
                $name[0] = strtolower($name[0]);
                $name = substr($name, 0, strlen($name) - 6);
            } else {
                $name = strtolower($name);
            }
        }

        $screenName = implode(DIRECTORY_SEPARATOR, $screenName);
        $actionName = $this->dispatcher->getActionName();

        if ($actionName !== NULL) {
            if (substr($actionName, -6) == 'Action') {
                $actionName = substr($actionName, 0, strlen($actionName) - 6);
            }
            $screenName .= DIRECTORY_SEPARATOR . $actionName;
        }

        return $screenName;
    }
    
    public function getResult($data) {
        if (empty($data) && !is_array($data)) {
            $data = $this->_vars;
        }
        
        $layoutResult = $screenResult = NULL;
        $screenPath = $this->getScreenPath();
        $layoutPath = $this->getLayoutPath();
        
        if (empty($layoutPath) && empty($screenPath)) {
            throw new TemplateCommandInvalid('getResult', 'TemplateHelper Core (NO LAYOUT AND NO SCREEN)');
        }

        $viewEngine = $this->getEngine();
        if ($layoutPath) {
            $layoutResult = $viewEngine->parse($layoutPath, $data, self::TYPE_LAYOUT);
        }

        if ($screenPath) {
            $screenResult = $viewEngine->parse($screenPath, $data, self::TYPE_SCREEN);
        }
        
        return $viewEngine->getResult($layoutResult, $screenResult);
    }

    public static function getBaseDirs($type) {
        $baseDirs = [];

        $baseTplDir = Context::env(ConfigItem::BASE_TPL_DIR, NULL, 'template'. DIRECTORY_SEPARATOR);
        $dirPrefix = Context::env(ConfigItem::TEMPLATE_PREFIX);

        if ($type == self::TYPE_SCREEN && !empty(View::$_screenDir)) {
            $baseDirs[] = $baseTplDir. View::$_screenDir;
        } elseif ($type == self::TYPE_LAYOUT && !empty(View::$_layoutDir)) {
            $baseDirs[] = $baseTplDir. View::$_layoutDir;
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

    /**
     * @return BaseTemplateEngine
     * @throws \Akari\system\ioc\DINotRegistered
     */
    public function getEngine() {
        return $this->getDI()->getShared('viewEngine');
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
