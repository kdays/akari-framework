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
    const TYPE_JS_INLINE = 2;
    const TYPE_CSS_INLINE = 3;

    protected $collections = [];

    const DEFAULT_COLLECTION_NAME = 'default';

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
            $prefix = substr($path, 0, 1);
            $data = substr($path, 1);

            if ($prefix == AssetCollection::PREFIX_FILE) {
                $result .= sprintf(
                    "<script src=\"%s\" type=\"text/javascript\"></script>\n",
                    $collection->execBehaviour($data, AssetsMgr::TYPE_JS)
                );
            } elseif ($prefix == AssetCollection::PREFIX_INLINE) {
                $result .= 
                    "<script type=\"text/javascript\">" .
                        $collection->execBehaviour($data, AssetsMgr::TYPE_JS_INLINE)
                    . "</script>\n";
            }
        }

        return $result;
    }

    public function outputCss($name = 'default') {
        $collection = $this->collection($name);
        $result = '';
        foreach ($collection->getCssPaths() as $path) {
            $prefix = substr($path, 0, 1);
            $data = substr($path, 1);

            if ($prefix == AssetCollection::PREFIX_FILE) {
                $result .= sprintf(
                    "<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" />\n",
                    $collection->execBehaviour($data, AssetsMgr::TYPE_CSS)
                );
            } elseif ($prefix == AssetCollection::PREFIX_INLINE) {
                $result .= 
                    "<style>" .
                        $collection->execBehaviour($data, AssetsMgr::TYPE_CSS_INLINE)
                    . "</style>\n";
            }
        }

        return $result;
    }


    public function addBehaviour($item) {
        return $this->collection(self::DEFAULT_COLLECTION_NAME)->addBehaviour($item);
    }

    public function addJs($path) {
        return $this->collection(self::DEFAULT_COLLECTION_NAME)->addJs($path);
    }

    public function addCss($path) {
        return $this->collection(self::DEFAULT_COLLECTION_NAME)->addCss($path);
    }

    public function setPrefix($prefix) {
        return $this->collection(self::DEFAULT_COLLECTION_NAME)->setPrefix($prefix);
    }
}
