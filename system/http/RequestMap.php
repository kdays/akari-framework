<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-06
 * Time: 18:34
 */

namespace Akari\system\http;

use Akari\system\util\TextUtil;
use Akari\system\ioc\Injectable;

abstract class RequestMap extends Injectable {

    private $_pValues = [];
    protected $valueRetCall = NULL;

    public static function createFromValues(array $values, callable $getter = NULL) {
        $model = new static($values, $getter);

        return $model;
    }

    public function __construct(array $values = NULL, callable $valueGetter = NULL, $skipCheck = FALSE) {
        if ($values !== NULL) {
            $this->_pValues = $values;
        }

        $this->valueRetCall = $valueGetter;
        $map = $this->getColumnMap();

        foreach($this as $key => $value){
            if ($key == '_pValues') {
                continue;
            }

            $reqKey = $map[$key] ?? $key;
            $this->$key = $this->getValue($reqKey, $value);
        }

        if (!$skipCheck) $this->checkParameters();
    }

    /// HttpMethod
    const METHOD_POST = 'POST';
    const METHOD_GET = 'GET';
    const METHOD_ALL = 'GP';

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
        if (TextUtil::exists($key, "[")) {
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

        // 调用接口来处理
        if ($this->valueRetCall !== NULL) {
            return call_user_func($this->valueRetCall, $key, $this->getFilter($key), $defaultValue);
        }

        $callMap = [
            self::METHOD_ALL => 'get',
            self::METHOD_GET => 'getQuery',
            self::METHOD_POST => 'getPost',
            NULL => 'get'
        ];

        $value = $this->request->{$callMap[$method]}($key, $this->getFilter($key), $defaultValue);
        if ($value === $defaultValue) return $defaultValue;

        return $value;
    }

    public function getFilter(string $key) {
        return 'default';
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
            $reqKey = $map[$key] ?? $key;
            if ($value !== NULL || $includeNull) {
                $result[ $reqKey ] = $value;
            }
        }

        return $result;
    }
}
