<?php
!defined("AKARI_PATH") && exit;

Class UploadHelper{
	protected static $h;
	public static function getInstance(){
		if(!isset(self::$h)){
			self::$h = new self();
		}

		return self::$h;
	}
    
	/**
	 * 是否是一个合法的上传文件
	 * 
	 * @param string $tmpName 上传表单中的tmp_name
	 * @return boolean
	 * @todo 如果使用moveFile方法的话，不必调用本函数。因为已经自动调用了
	 */
	public function isUploadedFile($tmpName){
		if (!$tmpName || $tmpName == 'none') {
			return false;
		} elseif (function_exists('is_uploaded_file') && !is_uploaded_file($tmpName) && !is_uploaded_file(str_replace('\\\\', '\\', $tmpName))) {
			return false;
		}

		return true;
	}

	public function getRandName($ext, $opts = Array()){
		return md5(uniqid()).".$ext";
	}

	public function getDefaultName($ext, $opts = Array()){
		return $opts['filename'].(empty($opts['ext']) ? "" : ".$ext");
	}
    
	/**
	 * 上传并移动文件
	 * 
	 * @param array $uploadForm 上传的表单数组
	 * @param string $saveDir 保存目录
	 * @param string $namePolicty 命名方式（默认为getRandName）
	 * @param array $namePolictyOptions 命名函数调用时的参数
	 * @param callable $callback 回调参数
	 * @param string $allowExt 允许的文件格式，不设定为设定中的allowUploadExt
	 * @throws UploadFileCannotAccess
	 * @return boolean|mixed
	 */
	public function moveFile($uploadForm, $saveDir, $namePolicty = NULL, $namePolictyOptions = Array(), $callback, $allowExt = NULL){
		if(empty($allowExt)){
			$allowExt = Context::$appConfig->allowUploadExt;
		}

		if(!$this->isUploadedFile($uploadForm['tmp_name'])){
			return FALSE;
		}

		$tmpName = explode(".", $uploadForm['name']);
		$fileExt = strtolower(end($tmpName));

		if(!in_array($fileExt, $allowExt)){
			return FALSE;
		}

		if($namePolicty == NULL){
			$namePolicty = Array($this, "getRandName");
		}

		$newName = call_user_func($namePolicty, $fileExt, $namePolictyOptions);
		$target = Context::$appBasePath.Context::$appConfig->uploadDir."/".$saveDir."/".$newName;

		if(!movefile($target, $uploadForm['tmp_name'])){
			return FALSE;
		}

		if($callback != NULL){
			call_user_func($callback, $saveDir."/".$newName, $target);
		}

		return $newName;
	}
}

Class UploadFileCannotAccess extends Exception{

}