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
use Akari\utility\TextHelper;
use Akari\system\ioc\Injectable;
use Akari\system\tpl\engine\BaseTemplateEngine;

class View extends Injectable{

    const TYPE_BLOCK = 'block';
    const TYPE_SCREEN = 'view';
    const TYPE_LAYOUT = 'layout';
    const TYPE_WIDGET = 'widget';

    public static $allTypes = [View::TYPE_SCREEN, View::TYPE_BLOCK, View::TYPE_LAYOUT, View::TYPE_WIDGET];

    protected $layout = 'default';
    protected $screen;

    protected static $_layoutDir;
    protected static $_screenDir;

    protected $registeredDirs = [];

    private $_vars = [];
    private $baseActionNs = NULL;

    protected $engines = [];
    protected static $_registeredExtensions = [];

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

    public function setViewDirs(array $dirs, string $viewType = NULL) {
        if ($viewType === NULL) {
            foreach (View::$allTypes as $type) {
                $this->registeredDirs[$type] = [];

                foreach ($dirs as $dir) {
                    $this->registeredDirs[$type][] = $dir . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR;
                }
            }

            return $this->registeredDirs;
        }

        $this->registeredDirs[$viewType] = $dirs;

        return $this->registeredDirs[$viewType];
    }

    /**
     * 新添加的viewDir在查找中会最优先查找
     * 此外如果设置了viewDir,此时defaultDir就无效了
     *
     * @param string $dir
     * @param string|NULL $viewType
     * @param bool $onAppDir
     * @return array|mixed
     */
    public function addViewDir(string $dir, string $viewType = NULL, $onAppDir = TRUE) {
        if ($onAppDir) {
            $dir = Context::$appEntryPath . $dir . DIRECTORY_SEPARATOR;
        }

        if ($viewType === NULL) {
            foreach (View::$allTypes as $type) {
                $this->addViewDir($dir . DIRECTORY_SEPARATOR . $type . DIRECTORY_SEPARATOR, $type, FALSE);
            }

            return $this->registeredDirs;
        }

        if (!isset($this->registeredDirs[$viewType])) {
            $this->registeredDirs[$viewType] = [];
        }
        array_unshift($this->registeredDirs[$viewType], $dir);

        return $this->registeredDirs[$viewType];
    }

    public function getViewDirs($viewType) {
        if (empty($this->registeredDirs[$viewType])) {
            $this->registeredDirs[$viewType] = $this->getDefaultViewDirs($viewType);
        }

        return $viewType === NULL ? $this->registeredDirs : $this->registeredDirs[$viewType];
    }

    public function getDefaultViewDirs($viewType) {
        $baseDir = Context::env(ConfigItem::BASE_TPL_DIR, NULL, 'template');
        $path = Context::$appEntryPath . $baseDir . DIRECTORY_SEPARATOR . $viewType . DIRECTORY_SEPARATOR;

        return [$path];
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

    public function getScreenPath() {
        $screenName = empty($this->screen) ? $this->getScreenName() : $this->screen;

        return $this->find($screenName, self::TYPE_SCREEN);
    }

    public function getLayoutPath() {
        $screenName = $this->layout;
        if (empty($this->layout)) {
            return NULL;
        }

        return $this->find($screenName, self::TYPE_LAYOUT);
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

    private $_screenResult = NULL;

    public function getResult($data) {
        if (empty($data) && !is_array($data)) {
            $data = $this->_vars;
        }

        $layoutResult = $screenResult = NULL;
        $screenPath = $this->getScreenPath();
        $layoutPath = $this->getLayoutPath();

        if (empty($layoutPath) && empty($screenPath)) {
            throw new ViewException("not found any available view file");
        }

        if ($screenPath) {
            $screenResult = $this->render($screenPath, $data, self::TYPE_SCREEN);
            $this->_screenResult = $screenResult;
        }

        if ($layoutPath) {
            $layoutResult = $this->render($layoutPath, $data, self::TYPE_LAYOUT);
        }

        if (empty($layoutResult)) {
            return $screenResult;
        }
        return $layoutResult;
    }

    public function getScreenResult() {
        return $this->_screenResult;
    }

    public function render(string $path, array $data, string $type = self::TYPE_BLOCK) {
        $viewEngine = $this->getViewEngine($path);
        return $viewEngine->parse($path, $data, $type);
    }

    public function find(string $tplName, string $type) {
        if (empty($tplName)) {
            return NULL;
        }

        $baseDirs = $this->getViewDirs($type);
        $tplName = str_replace(NAMESPACE_SEPARATOR, DIRECTORY_SEPARATOR, $tplName);
        $dirPrefix = Context::env(ConfigItem::TEMPLATE_PREFIX);

        foreach ($baseDirs as $baseDir) {
            foreach (self::$_registeredExtensions as $suffix) {
                if (!empty($dirPrefix)) {
                    if (file_exists($tplPath = $baseDir . $dirPrefix . $tplName . $suffix)) {
                        return realpath($tplPath);
                    }
                }

                if (file_exists($tplPath = $baseDir . $tplName . $suffix)) {
                    return realpath($tplPath);
                }
            }
        }

        if ($tplName != 'default') {
            return $this->find("default", $type);
        }

        return FALSE;
    }

    /**
     * @param string $fileName
     * @return BaseTemplateEngine
     * @throws ViewException
     */
    public function getViewEngine(string $fileName) {
        $ext = TextHelper::getFileExtension($fileName);
        if (!isset($this->engines['.' . $ext])) {
            throw new ViewException("No Available View Engine: " . $fileName);
        }

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

    public static function render4Data($viewContent, array $data) {
        $view = function ($pView, $assignData) {
            ob_start();
            @extract($assignData, EXTR_PREFIX_SAME, 'a_');
            include $pView;
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        };

        return $view($viewContent, $data);
    }
}
