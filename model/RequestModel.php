<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:31
 */

namespace Akari\model;

Class RequestModel extends Model {

    const METHOD_POST = 'P';
    const METHOD_GET = 'G';
    const METHOD_GET_AND_POST = 'GP';

    /**
     * @var array $keyMapList 映射表 RequestModel参数 => URL参数
     */
    protected static $keyMapList = [];

    public function __construct(){
        $map = static::$keyMapList;

        foreach($this as $key => $value){
            $reqKey = isset($map[$key]) ? $map[$key] : $key;

            $value = GP($reqKey, $this->_getKeyMethod($key));
            if($value !== NULL){
                $this->$key = $value;
            }
        }

        $this->_checkParams();

        return $this;
    }

    /**
     * 如果有mapList，则按照mapList反向导出数组
     *
     * @param bool $toURL 转换是以URL为准还是以RequestModel参数为准
     * @param bool $includeNull
     * @return array
     */
    public function toArray($toURL = false, $includeNull = false) {
        $map = !$toURL ? array_flip(static::$keyMapList) : static::$keyMapList;
        $result = [];

        foreach ($this as $key => $value) {
            $reqKey = isset($map[$key]) ? $map[$key] : $key;
            if ($value !== NULL || $includeNull) {
                $result[ $reqKey ] = $value;
            }
        }

        return $result;
    }

    /**
     * 检查参数回调，在取值时调用，可以通过$this->键名重设值
     */
    protected function _checkParams(){

    }

    /**
     * 获得的方式，是允许GET还是POST 方式见METHOD_*
     *
     * @param string $key 键名
     * @return string
     */
    protected function _getKeyMethod($key){
        return RequestModel::METHOD_GET_AND_POST;
    }
}