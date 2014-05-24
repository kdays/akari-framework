<?php
!defined("AKARI_PATH") && exit;

Class RequestModel extends Model{

	const METHOD_POST = 'P';
	const METHOD_GET = 'G';
	const METHOD_GET_AND_POST = 'GP';

	public function __construct(){
		return $this->values();
	}

	/**
	 * 获得值，在初始化时调用
	 *
	 * @return void
	 **/
	public function values(){
		foreach($this as $key => $value){
			$req = GP($key, $this->_getMethod($key));
			if($req)	$this->$key = $this->_checkParams($key, $req);
		}

		return $this;
	}
    
	/**
	 * 检查每个参数，在取值时调用，然后根据返回值可以重新设定参数进行过滤
	 * 
	 * @param string $key 键名
	 * @param string $reqValue 内容
	 * @return mixed
	 */
	public function _checkParams($key, $reqValue){
		return $reqValue;
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
}