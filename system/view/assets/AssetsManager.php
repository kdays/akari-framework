<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/27
 * Time: 0:22
 */

namespace Akari\system\view\assets;

class AssetsManager {

    const TYPE_CSS = 0;
    const TYPE_JS = 1;
    const TYPE_JS_INLINE = 2;
    const TYPE_CSS_INLINE = 3;
    const TYPE_IMAGES = 4;

    protected $collections = [];

    const DEFAULT_COLLECTION_NAME = 'default';

    /**
     * @param $name
     * @return AssetsCollection
     */
    public function collection($name) {
        if (!array_key_exists($name, $this->collections)) {
            $collection = new AssetsCollection($name);
            $this->collections[$name] = $collection;
        }

        return $this->collections[$name];
    }

    public function outputJs($name = 'default') {
        $collection = $this->collection($name);

        $dom = new \DOMDocument();
        foreach ($collection->getItems([self::TYPE_JS, self::TYPE_JS_INLINE]) as $assetItem) {

            $resultItem = $collection->execBehaviour($assetItem);
            if ($assetItem->type == AssetsManager::TYPE_JS) {
                $el = $dom->createElement("script");
                $el->setAttribute("src", $resultItem->content);
                $el->setAttribute("type", "text/javascript");

                foreach ($resultItem->htmlOptions as $key => $value) {
                    $el->setAttribute($key, $value);
                }

                $dom->appendChild($el);
            } elseif ($assetItem->type == AssetsManager::TYPE_JS_INLINE) {
                $el = $dom->createElement("script");
                $el->textContent = $resultItem->content;

                foreach ($resultItem->htmlOptions as $key => $value) {
                    $el->setAttribute($key, $value);
                }

                $dom->appendChild($el);
            }
        }


        return $dom->saveHTML();
    }

    public function outputCss($name = 'default') {
        $collection = $this->collection($name);

        $dom = new \DOMDocument();
        foreach ($collection->getItems([self::TYPE_CSS, self::TYPE_CSS_INLINE]) as $assetItem) {
            $resultItem = $collection->execBehaviour($assetItem);
            if ($assetItem->type == AssetsManager::TYPE_CSS) {
                $el = $dom->createElement("link");
                $el->setAttribute("rel", "stylesheet");
                $el->setAttribute("href", $resultItem->content);
                $el->setAttribute("type", "text/css");

                foreach ($resultItem->htmlOptions as $key => $value) {
                    $el->setAttribute($key, $value);
                }

                $dom->appendChild($el);
            } elseif ($assetItem->type == AssetsManager::TYPE_CSS_INLINE) {
                $el = $dom->createElement("style");
                $el->textContent = $resultItem->content;
                $el->setAttribute("type", "text/css");

                foreach ($resultItem->htmlOptions as $key => $value) {
                    $el->setAttribute($key, $value);
                }

                $dom->appendChild($el);
            }
        }


        return $dom->saveHTML();
    }


    public function addBehaviour($item) {
        return $this->collection(self::DEFAULT_COLLECTION_NAME)->addBehaviour($item);
    }

    public function addJs($path, $options = []) {
        return $this->collection(self::DEFAULT_COLLECTION_NAME)->addJs($path, $options);
    }

    public function addCss($path, $options = []) {
        return $this->collection(self::DEFAULT_COLLECTION_NAME)->addCss($path, $options);
    }

    public function addInlineCss($content, $options = []) {
        return $this->collection(self::DEFAULT_COLLECTION_NAME)->addInlineCss($content, $options);
    }

    public function addInlineJs($content, $options = []) {
        return $this->collection(self::DEFAULT_COLLECTION_NAME)->addInlineJs($content, $options);
    }

    public function setPrefix($prefix) {
        return $this->collection(self::DEFAULT_COLLECTION_NAME)->setPrefix($prefix);
    }

    public function reset() {
        $this->collections = [];
    }

}
