<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/4/21
 * Time: 上午10:00.
 */

namespace Akari\system\tpl\asset;

class AssetCollection
{
    private $_id;
    private $_css = [];
    private $_js = [];
    private $_prefix = '';

    const PREFIX_FILE = 'F';
    const PREFIX_INLINE = 'I';

    private $_behaviour = [];

    public function __construct($id)
    {
        $this->_id = $id;
    }

    public function setPrefix($prefix)
    {
        $this->_prefix = $prefix;

        return $this;
    }

    public function addJs($path)
    {
        $this->_js[] = self::PREFIX_FILE.$this->_prefix.$path;

        return $this;
    }

    public function addCss($path)
    {
        $this->_css[] = self::PREFIX_FILE.$this->_prefix.$path;

        return $this;
    }

    public function addBehaviour($cls)
    {
        $this->_behaviour[] = $cls;

        return $this;
    }

    public function execBehaviour($path, $type)
    {
        foreach ($this->_behaviour as $item) {
            if (is_callable($item)) {
                $path = $item($path, $type);
            } else {
                $path = $item::execute($path, $type);
            }
        }

        return $path;
    }

    /**
     * @return array
     */
    public function getCssPaths()
    {
        return $this->_css;
    }

    public function getJsPaths()
    {
        return $this->_js;
    }

    public function setCssPaths($paths)
    {
        $this->_css = $paths;
    }

    public function setJsPaths($paths)
    {
        $this->_js = $paths;
    }

    public function addInlineCss($inlineCss)
    {
        $this->_css[] = self::PREFIX_INLINE.$inlineCss;
    }

    public function addInlineJs($inlineJs)
    {
        $this->_js[] = self::PREFIX_INLINE.$inlineJs;
    }
}
