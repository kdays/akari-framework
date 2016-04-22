<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/4/21
 * Time: 上午9:46
 */

namespace Akari\system\tpl\asset;

class AssetsMgr {
    
    const TYPE_CSS = 0;
    const TYPE_JS = 1;
    
    protected $collections = [];

    /**
     * @param $name
     * @return AssetCollection
     */
    public function collection($name) {
        if (!array_key_exists($name, $this->collections)) {
            $collection = new AssetCollection($name);
            $this->collections[$name] = $collection;
        }
        
        return $this->collections[$name];
    }
    
    public function outputJs($name = 'default') {
        $collection = $this->collection($name);
        $result = '';
        foreach ($collection->getJsPaths() as $path) {
            $result .= sprintf("<script src=\"%s\" type=\"text/javascript\"></script>\n", $collection->execBehaviour($path, AssetsMgr::TYPE_JS));
        }

        return $result;
    }
    
    public function outputCss($name = 'default') {
        $collection = $this->collection($name);
        $result = '';
        foreach ($collection->getCssPaths() as $path) {
            $result .= sprintf("<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" />\n", $collection->execBehaviour($path, AssetsMgr::TYPE_CSS));
        }
        
        return $result;
    }
    
    
    public function addBehaviour($item) {
        return $this->collection('default')->addBehaviour($item);
    }
    
    public function addJs($path) {
        return $this->collection('default')->addJs($path);
    }
    
    public function addCss($path) {
        return $this->collection('default')->addCss($path);
    }
    
    public function setPrefix($prefix) {
        return $this->collection('default')->setPrefix($prefix);
    }
}