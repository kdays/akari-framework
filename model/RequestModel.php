<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:31
 */

namespace Akari\model;

use Akari\system\security\FilterFactory;

abstract class RequestModel extends Model {

    private $_pValues = [];

    public static function createFromValues(array $values) {
        $model = new static($values);

        return $model;
    }

    const METHOD_POST = 'P';
    const METHOD_GET = 'G';
    const METHOD_GET_AND_POST = 'GP';

    /**
     * 检查参数回调，在取值时调用，可以通过$this->键名重设值
     */
    abstract protected function checkParameters();

    /**
     * 获得的方式，是允许GET还是POST 方式见METHOD_*
     *
     * @param string $key 键名
     * @return string
     */
    abstract protected function getKeyMethod($key);

    /**
     * 映射表
     * RequestModel参数 => URL参数
     *
     * @return array
     */
    abstract protected function getColumnMap();

    public function getValue($key, $defaultValue = NULL) {
        $method = $this->getKeyMethod($key);
        if (in_string($key, "[")) {
            $startPos = strpos($key, '[');

            $baseKey = substr($key, 0, $startPos);
            $subKey =  substr($key, $startPos + 1, -1);

            $values = $this->requestValue($baseKey, $method, $defaultValue);
            if (array_key_exists($subKey, $values)) {
                return $values[$subKey];
            }

            return $defaultValue;
        }

        return $this->requestValue($key, $method, $defaultValue);
    }

    public function requestValue($key, $method, $defaultValue) {
        if (isset($this->_pValues[$key])) {
            return $this->_pValues[$key];
        }

        if ($method == self::METHOD_GET_AND_POST) {
            $value = isset($_REQUEST[$key]) ? $_REQUEST[$key] : $defaultValue;
        } elseif ($method == self::METHOD_GET) {
            $value = isset($_GET[$key]) ? $_GET[$key] : $defaultValue;
        } else {
            $value = isset($_POST[$key]) ? $_POST[$key] : $defaultValue;
        }

        if ($value === $defaultValue) return $defaultValue;
        return $this->filter($key, $value);
    }

    public function __construct($values = NULL) {
        if ($values !== NULL) {
            $this->_pValues = $values;
        }

        $map = $this->getColumnMap();

        foreach($this as $key => $value){
            if ($key == '_pValues') {
                continue;
            }

            $reqKey = isset($map[$key]) ? $map[$key] : $key;
            $this->$key = $this->getValue($reqKey, $value);
        }

        $this->checkParameters();
    }

    public function filter($key, $value) {
        return FilterFactory::doFilter($value, 'default');
    }

    /**
     * 如果有mapList，则按照mapList反向导出数组
     *
     * @param bool $toURL 转换是以URL为准还是以RequestModel参数为准
     * @param bool $includeNull
     * @return array
     */
    public function toArray($toURL = FALSE, $includeNull = FALSE) {
        $map = $this->getColumnMap();
        if (!$toURL)    $map = array_flip($map);
        $result = [];

        foreach ($this as $key => $value) {
            $reqKey = isset($map[$key]) ? $map[$key] : $key;
            if ($value !== NULL || $includeNull) {
                $result[ $reqKey ] = $value;
            }
        }

        return $result;
    }
}
