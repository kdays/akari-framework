<?php
!defined("AKARI_PATH") && exit;

Class UploadHelper{
	protected static $h;
	protected static function getInstance(){
		if(!isset(self::$h)){
			self::$h = new self();
		}

		return self::$h;
	}

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

	public function moveFile($uploadForm, $saveDir, $namePolicty = NULL, $namePolictyOptions = Array(), $callback, $allowExt = NULL){
		if(empty($allowExt)){
			$allowExt = Context::$appConfig->allowUploadExt;
		}

		if(!$this->isUploadedFile($uploadForm['tmp_name'])){
			throw new UploadFileCannotAccess();
		}

		$fileExt = strtolower(end(explode(".", $uploadForm['name'])));
		if(!in_array($fileExt, $allowExt)){
			return FALSE;
		}

		if($namePolicty == NULL){
			$namePolicty = Array(self, "getRandName");
		}

		$newName = call_user_func($namePolicty, $fileExt, $namePolictyOptions);
		$target = Context::$appBasePath.Context::$appConfig->uploadDir.$saveDir.$namePolicty;
	
		if(!movefile($uploadForm['tmp_name'], $target)){
			throw new UploadFileCannotAccess();
		}

		if($callback != NULL){
			call_user_func($callback, $newName, $target);
		}

		return $newName;
	}
}

Class UploadFileCannotAccess extends Exception{

}