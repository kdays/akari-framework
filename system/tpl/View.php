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
    private $baseActionNs = NULL;
    private $defaultLayoutName = NULL;
    
    public function setDefaultLayoutName($layoutName) {
        $this->defaultLayoutName = $layoutName;
    }

    /**
     * 将参数绑定到模板 别的不会执行  
     *
     * @param string|array $key
     * @param mixed $value
     * @return array
     */
    public function bindVar($key, $value = NULL) {
        if (is_array($key)) {
            $this->_vars =  array_merge($this->_vars, $key);
        } else {
            $this->_vars[$key] = $value;
        }
    }
    
    public function bindVars($values, $isMerge = True) {
        $this->_vars = $isMerge ? array_merge($this->_vars, $values) : $values;
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

    public function setBaseActionNs($name) {
        $this->baseActionNs = $name;
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
            $screenPath = $this->dispatcher->findWay($screenName, $screenDir. DIRECTORY_SEPARATOR, $suffix, false);
            if ($screenPath) break;
        }

        return $screenPath;
    }
    
    public function getLayoutPath() {
        $screenName = $this->layout;
        if (empty($this->layout)) {
            $screenName = !empty($this->defaultLayoutName) ? $this->defaultLayoutName : $this->getScreenName();
        }
       
        $suffix = Context::$appConfig->templateSuffix;

        $baseDirs = $this->getBaseDirs(self::TYPE_LAYOUT);

        $layoutPath = NULL;
        foreach ($baseDirs as $layoutDir) {
            $layoutPath = $this->dispatcher->findWay($screenName, $layoutDir. DIRECTORY_SEPARATOR, $suffix, false);
            if ($layoutPath) break;
        }

        return $layoutPath;
    }
    
    public function setBaseViewDir($viewDir, $onAppDir = TRUE) {
        if ($onAppDir) {
            $viewDir = Context::$appEntryPath. $viewDir;
        }
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
        if ($this->baseActionNs !== NULL) { 
            // 如果设置baseActionNs时 如果前面有baseActionNs就移去
            // 在设置baseViewDir时,为了降低目录层级时,十分有用
            
            $len = strlen($this->baseActionNs);
            
            if (substr($screenName, 0, $len) == $this->baseActionNs) {
                $screenName = substr($screenName, $len);
            }
        }
        
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
            throw new TemplateNotFound('NOT_FOUND_LAYOUT_OR_SCREEN');
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

        $defaultWebPath = Context::$appEntryPath. 'template'. DIRECTORY_SEPARATOR;
        $baseTplDir = Context::env(ConfigItem::BASE_TPL_DIR, NULL, $defaultWebPath);
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
        
        if ($type == self::TYPE_BLOCK || $type == self::TYPE_WIDGET || $type == self::TYPE_LAYOUT) {
            $baseDirs[] = $defaultWebPath. $type. DIRECTORY_SEPARATOR;
        }
        
        return $baseDirs;
    }

    public static function find($tplName, $type) {
        $baseDirs = self::getBaseDirs($type);

        $suffix = Context::$appConfig->templateSuffix;
        $tplName = str_replace(NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $tplName);
        
        foreach ($baseDirs as $baseDir) {
            //$baseDir = Context::$appEntryPath. $baseDir;
            
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
