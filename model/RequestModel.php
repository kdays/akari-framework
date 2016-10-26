<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:31
 */

namespace Akari\model;

abstract Class RequestModel extends Model {

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

    public function __construct(){
        $map = $this->getColumnMap();

        foreach($this as $key => $value){
            $reqKey = isset($map[$key]) ? $map[$key] : $key;
            $method = $this->getKeyMethod($key);
            
            // 处理Map的特殊字段 比如[]
            if (in_string($reqKey, '[')) { // eg. voteOpt => specialCfg[voteOpt] 
                $startPos = strpos($reqKey, '[');
                
                $baseKey = substr($reqKey, 0, $startPos);
                $subKey =  substr($reqKey, $startPos + 1, -1);
                
                $values = GP($baseKey, $method);
                if (array_key_exists($subKey, $values)) {
                    $value = $values[$subKey];
                }
            } else {
                $value = GP($reqKey, $method);
            }
            
            if($value !== NULL){
                $this->$key = $value;
            }
        }

        $this->checkParameters();

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