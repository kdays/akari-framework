<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/4/21
 * Time: 上午10:00
 */

namespace Akari\system\tpl\asset;


class AssetCollection {

    private $_id;
    private $_css = [];
    private $_js = [];
    private $_prefix = '';
    
    public function __construct($id) {
        $this->_id = $id;
    }
    
    public function setPrefix($prefix) {
        $this->_prefix = $prefix;
        return $this;
    }

    public function addJs($path) {
        $this->_js[] = $this->_prefix. $path;
        return $this;
    }
    
    public function addCss($path) {
        $this->_css[] = $this->_prefix. $path;
        return $this;
    }

    /**
     * @return array
     */
    public function getCssPaths() {
        return $this->_css;
    }
    
    public function getJsPaths() {
        return $this->_js;
    }
}