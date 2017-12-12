<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/2
 * Time: 上午8:44
 */

namespace Akari\system\tpl;

use Akari\Context;
use Akari\config\ConfigItem;
use Akari\system\ioc\Injectable;
use Akari\system\tpl\engine\BaseTemplateEngine;
use Akari\utility\TextHelper;

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

    protected $engines = [];
    protected static $_registeredExtensions = [];

    public function setDefaultLayoutName($layoutName) {
        $this->defaultLayoutName = $layoutName;
    }

    /**
     * 将参数绑定到模板 别的不会执行  
     *
     * @param string|array $key
     * @param mixed $value
     */
    public function bindVar($key, $value = NULL) {
        if (is_array($key)) {
            $this->_vars =  array_merge($this->_vars, $key);
        } else {
            $this->_vars[$key] = $value;
        }
    }

    public function bindVars($values, $isMerge = TRUE) {
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
        return self::find($screenName, self::TYPE_SCREEN);
    }

    public function getLayoutPath() {
        $screenName = $this->layout;
        if (empty($this->layout)) {
            $screenName = !empty($this->defaultLayoutName) ? $this->defaultLayoutName : $this->getScreenName();
        }

        return self::find($screenName, self::TYPE_LAYOUT);
    }

    public function setBaseViewDir($viewDir, $onAppDir = TRUE) {
        if ($onAppDir) {
            $viewDir = Context::$appEntryPath . $viewDir;
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

        $viewEngine = NULL;
        if ($screenPath) {
            $viewEngine = $this->getViewEngine($screenPath);
            $screenResult = $viewEngine->parse($screenPath, $data, self::TYPE_SCREEN);
        }

        if ($layoutPath) {
            $viewEngine = $this->getViewEngine($layoutPath);
            $layoutResult = $this->getViewEngine($layoutPath)->parse($layoutPath, $data, self::TYPE_LAYOUT);
        }

        return $viewEngine->getResult($layoutResult, $screenResult);
    }

    public static function getBaseDirs(string $type) {
        $baseDirs = [];

        $defaultWebPath = Context::$appEntryPath . 'template' . DIRECTORY_SEPARATOR;
        $baseTplDir = realpath(Context::env(ConfigItem::BASE_TPL_DIR, NULL, $defaultWebPath)) . DIRECTORY_SEPARATOR;
        if (empty($baseTplDir)) {
            $baseTplDir = $defaultWebPath;
        }

        $dirPrefix = Context::env(ConfigItem::TEMPLATE_PREFIX);

        if ($type == self::TYPE_SCREEN && !empty(View::$_screenDir)) {
            $baseDirs[] = $baseTplDir . View::$_screenDir;
        } elseif ($type == self::TYPE_LAYOUT && !empty(View::$_layoutDir)) {
            $baseDirs[] = $baseTplDir . View::$_layoutDir;
        }

        if ($dirPrefix) {
            $baseDirs[] =  $baseTplDir . $type . DIRECTORY_SEPARATOR . $dirPrefix . DIRECTORY_SEPARATOR;
        }

        $baseDirs[] =  $baseTplDir . $type . DIRECTORY_SEPARATOR;

        if ($type == self::TYPE_BLOCK || $type == self::TYPE_WIDGET || $type == self::TYPE_LAYOUT) {
            $baseDirs[] = $defaultWebPath . $type . DIRECTORY_SEPARATOR;
        }

        return $baseDirs;
    }

    public static function find(string $tplName, string $type) {
        $baseDirs = self::getBaseDirs($type);
        $tplName = str_replace(NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $tplName);

        foreach ($baseDirs as $baseDir) {
            foreach (self::$_registeredExtensions as $suffix) {
                if (file_exists($tplPath = $baseDir . $tplName . $suffix)) {
                    return realpath($tplPath);
                }
            }

            foreach (self::$_registeredExtensions as $suffix) {
                if (file_exists($tplPath = $baseDir . "default" . $suffix)) {
                    return realpath($tplPath);
                }
            }
        }

        throw new TemplateNotFound($type . "/" . $tplName);
    }

    public function getViewEngine(string $fileName) {
        $ext = TextHelper::getFileExtension($fileName);
        return $this->engines["." . $ext];
    }

    /**
     * @return BaseTemplateEngine[]
     */
    public function getEngines() {
        return $this->engines;
    }

    public function registerEngine(string $extension, $engine) {
        if ($extension[0] != '.') {
            $extension = "." . $extension;
        }

        if (is_callable($engine)) {
            $this->engines[ $extension ] = $engine();
        } else {
            $this->engines[ $extension ] = $engine;
        }
        self::$_registeredExtensions[] = $extension;
    }
}
