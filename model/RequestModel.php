<?php
!defined("AKARI_PATH") && exit;

Class RequestModel extends Model{

	const METHOD_POST = 'P';
	const METHOD_GET = 'G';
	const METHOD_GET_AND_POST = 'GP';

	public function __construct(){
		return $this->reloadValue();
	}

	/**
	 * 获得值，在初始化时调用
	 *
	 * @return void
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