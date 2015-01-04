<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:31
 */

namespace Akari\model;

use Akari\utility\helper\Logging;

Class RequestModel {

    use Logging;

    const METHOD_POST = 'P';
    const METHOD_GET = 'G';
    const METHOD_GET_AND_POST = 'GP';

    public function __construct(){
        return $this->reloadValue();
    }

    public static $m = [];
    public static function getRequest() {
        $class = get_called_class();
        if (!isset(self::$m[$class])) {
            self::$m[ $class ] = new $class;
        }

        return self::$m[ $class ];
    }

    /**
     * 获得值，在初始化时调用
     *
     * @return RequestModel
     **/
    public function reloadValue(){
        $map = $this->_mapList();

        foreach($this as $key => $value){
            $reqKey = $key;
            if(isset($map[$key])){
                $reqKey = $map[$key];
            }

            $value = GP($reqKey, $this->_getMethod($key));
            if($value !== NULL){
                $this->$key = $value;
            }
        }

        $this->_checkParams();

        return $this;
    }

    /**
     * 如果有mapList，则按照mapList反向导出数组
     */
    public function toArray() {
        $map = $this->_mapList();
        $result = [];

        foreach ($this as $key => $value) {
            $reqKey = $key;
            if(isset($map[$key])){
                $reqKey = $map[$key];
            }

            if ($value !== NULL) {
                $result[ $reqKey ] = $value;
            }
        }

        return $result;
    }

    /**
     * 检查参数回调，在取值时调用，可以通过$this->键名重设值
     */
    public function _checkParams(){

    }


    /**
     * 获得的方式，是允许GET还是POST 方式见METHOD_*
     *
     * @param string $key 键名
     * @return string
     */
    public function _getMethod($key){
        return RequestModel::METHOD_GET_AND_POST;
    }

    /**
     * 映射地图，通过地图你可以任意的值映射到模型中
     *
     * @return array
     **/
    public function _mapList(){
        return Array();
    }
}