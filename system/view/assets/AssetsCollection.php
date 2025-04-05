<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/27
 * Time: 0:27
 */

namespace Akari\system\view\assets;

class AssetsCollection {

    private $_id;

    protected $items = [];

    private $_prefix = '';

    const PREFIX_FILE = 'F';
    const PREFIX_INLINE = 'I';

    private $_behaviour = [];

    public function __construct($id) {
        $this->_id = $id;
    }

    public function setPrefix($prefix) {
        $this->_prefix = $prefix;

        return $this;
    }

    public function addJs($path, $options = []) {
        foreach ($this->items as $item) {
            if ($item->type == AssetsManager::TYPE_JS && $item->content == $path) {
                return $this; // 直接不重复添加
            }
        }
        $this->items[] = AssetContent::init(AssetsManager::TYPE_JS, $path, $options);

        return $this;
    }

    public function addCss($path, $options = []) {
        foreach ($this->items as $item) {
            if ($item->type == AssetsManager::TYPE_CSS && $item->content == $path) {
                return $this; // 直接不重复添加
            }
        }
        $this->items[] = AssetContent::init(AssetsManager::TYPE_CSS, $path, $options);

        return $this;
    }

    public function addBehaviour($cls) {
        if (!in_array($cls, $this->_behaviour)) {
            $this->_behaviour[] = $cls;
        }

        return $this;
    }

    public function execBehaviour(AssetContent $content) {
        $targetResult = clone $content;

        foreach ($this->_behaviour as $item) {
            if (is_callable($item)) {
                $item($targetResult);
            } else {
                /** @var IAssetsBehaviour $item */
                $item::execute($targetResult);
            }
        }

        return $targetResult;
    }


    public function addInlineCss($inlineCss, $options = []) {
        $this->items[] = AssetContent::init(AssetsManager::TYPE_CSS_INLINE, $inlineCss, $options);
        return $this;
    }

    public function addInlineJs($inlineJs, $options = []) {
        $this->items[] = AssetContent::init(AssetsManager::TYPE_JS_INLINE, $inlineJs, $options);
        return $this;
    }

    /**
     * @param $types
     * @return \Generator
     */
    public function getItems($types = []) {
        foreach ($this->items as $item) {
            if (empty($types) || in_array($item->type, $types)) {
                /** @var AssetContent $item */
                yield $item;
            }
        }
    }

    public function reset() {
        $this->items = [];
    }

    public function updateItems($items) {
        $this->items = $items;
    }

}
