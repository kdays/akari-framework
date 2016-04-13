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

// 
class View {

    /**
     * 将参数绑定到模板 别的不会执行  
     *
     * @param string|array $key
     * @param mixed $value
     * @return array
     */
    public function bindVar($key, $value = NULL) {
        TemplateHelper::assign($key, $value);
    }
    
    public function hasVar($key) {
        $vars = TemplateHelper::getAssignValues();
        return array_key_exists($key, $vars);
    }
    
    public function setLayout($layoutName) {
        ViewHelper::setLayout($layoutName);
    }

    public function setScreen($screenName) {
        ViewHelper::setScreen($screenName);
    }
    
    public function getLayout() {
        return ViewHelper::$layout;
    }
    
    public function getScreen() {
        return ViewHelper::$screen;
    }
    
    public function setBaseViewDir($viewDir) {
        Context::env(ConfigItem::BASE_TPL_DIR, $viewDir);
    }
    
    public function setLayoutDir($layoutDir) {
        ViewHelper::setLayoutDir($layoutDir);
    }
    
    public function setScreenDir($screenDir) {
        ViewHelper::setScreenDir($screenDir);
    }

}