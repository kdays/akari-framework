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
        $result = '';
        foreach ($collection->getJsPaths() as $path) {
            $prefix = substr($path, 0, 1);
            $data = substr($path, 1);

            if ($prefix == AssetsCollection::PREFIX_FILE) {
                $result .= sprintf(
                    "<script src=\"%s\" type=\"text/javascript\"></script>\n",
                    $collection->execBehaviour($data, self::TYPE_JS)
                );
            } elseif ($prefix == AssetsCollection::PREFIX_INLINE) {
                $result .=
                    "<script type=\"text/javascript\">" .
                    $collection->execBehaviour($data, self::TYPE_JS_INLINE)
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

            if ($prefix == AssetsCollection::PREFIX_FILE) {
                $result .= sprintf(
                    "<link rel=\"stylesheet\" href=\"%s\" type=\"text/css\" />\n",
                    $collection->execBehaviour($data, self::TYPE_CSS)
                );
            } elseif ($prefix == AssetsCollection::PREFIX_INLINE) {
                $result .=
                    "<style>" .
                    $collection->execBehaviour($data, self::TYPE_CSS_INLINE)
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
