<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/19
 * Time: 21:48
 */

namespace Akari\system\view;


use Akari\exception\AkariException;
use Akari\system\ioc\Injectable;
use Akari\system\util\ArrayUtil;
use Akari\system\util\TextUtil;
use Akari\system\view\engine\BaseViewEngine;

class View extends Injectable {

    const TYPE_BLOCK = 'block';
    const TYPE_SCREEN = 'view';
    const TYPE_LAYOUT = 'layout';

    protected $registeredDirs = [];
    public static $allTypes = [View::TYPE_SCREEN, View::TYPE_BLOCK, View::TYPE_LAYOUT];

    protected static $_registerExtensions = [];
    protected $vars = [];

    protected $layoutName;
    protected $screenName;
    protected $baseActionNs;

    protected $_screenResult = NULL;

    public function setViewDirs(string $viewType, array $paths, $merge = FALSE) {
        $beforeDirs = $merge ? $this->getViewDirs($viewType) : [];
        $this->registeredDirs[$viewType] = array_unique(array_merge($beforeDirs, $paths));
    }

    public function getViewDirs(?string $viewType) {
        if (empty($this->registeredDirs[$viewType]) && $viewType !== NULL) {
            return [];
        }

        return $viewType === NULL ? $this->registeredDirs : $this->registeredDirs[$viewType];
    }

    public function addViewDir(string $dir, ?string $viewType) {
        if ($viewType === NULL) {
            foreach (self::$allTypes as $type) {
                $newDir = str_replace(
                    DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,
                    DIRECTORY_SEPARATOR,
                    $dir . DIRECTORY_SEPARATOR . $type);
                $this->addViewDir($newDir, $type);
            }

            return $this->registeredDirs;
        }

        if (!isset($this->registeredDirs[$viewType])) {
            $this->registeredDirs[$viewType] = [];
        }

        $dir = str_replace(
            DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR,
            DIRECTORY_SEPARATOR,
            $dir);
        array_unshift($this->registeredDirs[$viewType], $dir);

        return $this->registeredDirs[$viewType];
    }

    public function bindVar(string $key, $value) {
        $this->vars[$key] = $value;
    }

    public function bindVars(array $values, $merge = TRUE) {
        $this->vars = $merge ? array_merge($this->vars, $values) : $values;
    }

    public function hasVar(string $key) {
        return array_key_exists($key, $this->vars);
    }

    public function getVar(?string $key) {
        if ($key === NULL) return $this->vars;
        return $this->vars[$key] ?? NULL;
    }

    public function setLayout($layoutName) {
        $this->layoutName = $layoutName;
    }

    public function setScreen(string $screenName) {
        $this->screenName = $screenName;
    }

    public function setActionNs(string $ns) {
        $this->baseActionNs = $ns;
    }

    public function getActionNs() {
        if (empty($this->baseActionNs)) {
            return $this->dispatcher->getAppActionNs();
        }

        return $this->baseActionNs;
    }

    public function getLayoutView() {
        if ($this->layoutName) {
           return $this->layoutName;
        }

        $layoutName = str_replace($this->getActionNs(), '', $this->dispatcher->getActionName());
        $layoutName = array_values(array_filter(explode(NAMESPACE_SEPARATOR, $layoutName)));

        if (count($layoutName) == 1) return 'default';
        return ArrayUtil::first($layoutName);
    }

    public function getScreenView() {
        if ($this->screenName) {
            return $this->screenName;
        }

        $viewName = str_replace($this->getActionNs(), '', $this->dispatcher->getActionName());
        $viewName = explode(NAMESPACE_SEPARATOR, basename($viewName, $this->dispatcher->getActionNameSuffix()));
        $viewName = array_values(array_filter($viewName));

        $lastViewName = array_pop($viewName);
        $lastViewName[0] = strtolower($lastViewName[0]);
        $viewName[] = $lastViewName;

        $actionMethod = $this->dispatcher->getActionMethod();

        $viewName[] = $actionMethod;
        return implode(DIRECTORY_SEPARATOR, $viewName);
    }

    public function find(string $name, string $type) {
        if (empty($name)) return NULL;

        $viewDirs = $this->getViewDirs($type);
        foreach ($viewDirs as $viewDir) {
            foreach (self::$_registerExtensions as $registerExtension => $_) {
                $path = $viewDir . DIRECTORY_SEPARATOR . $name . $registerExtension;
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return NULL;
    }

    public function getResult(?array $data) {
        if (empty($data) && !is_array($data)) {
            $data = $this->vars;
        }

        $layoutResult = $screenResult = NULL;
        $screenPath = $this->find($this->getScreenView(), View::TYPE_SCREEN);
        $layoutPath = $this->find($this->getLayoutView(), View::TYPE_LAYOUT);

        if (empty($screenPath) && empty($layoutPath)) {
            throw new AkariException("not found any available view file");
        }

        if ($screenPath) {
            $screenResult = $this->render($screenPath, $data, View::TYPE_SCREEN);
            $this->_screenResult = $screenResult;
        }

        if ($layoutPath) {
            $layoutResult = $this->render($layoutPath, $data, View::TYPE_LAYOUT);
        }

        if (empty($layoutResult)) return $screenResult;
        return $layoutResult;
    }

    public function getScreenResult() {
        return $this->_screenResult;
    }

    public function render(string $tplPath, array $data, string $type = self::TYPE_BLOCK) {
        $viewEngine = $this->getViewEngine($tplPath);

        return $viewEngine->parse($tplPath, $data, $type);
    }

    /**
     * @param string $fileName
     * @return BaseViewEngine
     * @throws AkariException
     */
    public function getViewEngine(string $fileName) {
        $ext = TextUtil::getFileExtension($fileName);
        if (!isset(self::$_registerExtensions["." . $ext])) {
            throw new AkariException("No Available View Engine:" . $ext);
        }

        return self::$_registerExtensions[".". $ext];
    }

    public function registerEngine(string $extension, $engine) {
        if ($extension[0] != '.') {
            $extension = "." . $extension;
        }

        if (is_callable($engine)) {
            self::$_registerExtensions[$extension] = $engine();
        } else {
            self::$_registerExtensions[$extension] = $engine;
        }
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
