<?php
namespace Akari\utility;

use Akari\Context;
use Exception;

!defined("AKARI_PATH") && exit;

Class UploadHelper{
	protected static $h;

    /**
     * @return UploadHelper
     */
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
     * 获得文件扩展名
     *
     * @param string $fileName 文件名
     * @param bool $diff 为true时取文件名段 false为扩展名
     * @return string
     */
    public function getExtension($fileName, $diff = FALSE) {
        $pathInfo = pathinfo($fileName);

        return $diff ? $pathInfo['filename'] : strtolower($pathInfo['extension']);
    }

	/**
	 * 上传并移动文件
	 *
	 * @param array $uploadForm 上传的表单数组
	 * @param string $saveDir 保存目录
	 * @param string $namePolicy 命名方式（默认为getRandName）
	 * @param array $namePolicyOptions 命名函数调用时的参数
	 * @param callable $callback 回调参数
	 * @param string $allowExt 允许的文件格式，不设定为设定中的allowUploadExt
	 * @return bool|mixed
	 * @throws UploadFileExtensionError
	 */
	public function moveFile($uploadForm, $saveDir, $namePolicy = NULL, $namePolicyOptions = Array(),$callback = NULL, $allowExt = NULL){
		if(empty($allowExt)){
			$allowExt = Context::$appConfig->allowUploadExt;
		}

		if(!$this->isUploadedFile($uploadForm['tmp_name'])){
			return FALSE;
		}

		$tmpName = explode(".", $uploadForm['name']);
		$fileExt = strtolower(end($tmpName));

		if(!in_array($fileExt, $allowExt)){
			throw new UploadFileExtensionError($fileExt, $allowExt);
		}

		if($namePolicy == NULL){
			$namePolicy = Array($this, "getRandName");
		}

		$newName = call_user_func($namePolicy, $fileExt, $namePolicyOptions);
		$target = Context::$appBasePath.
			Context::$appConfig->uploadDir.DIRECTORY_SEPARATOR.
			$saveDir.DIRECTORY_SEPARATOR.$newName;

		if(!movefile($target, $uploadForm['tmp_name'])){
			return FALSE;
		}

		if($callback != NULL){
			call_user_func($callback, $saveDir."/".$newName, $target);
		}

		return $newName;
	}

    /**
     * 快捷保存，在路径复杂时使用这个进行简单的判断
     * 路径默认在前面加上了uploadDir，不用额外添加
     *
     * @param array|string $uploadForm 上传表单或文件内容
     * @param string $savePath 保存路径
     * @param array $allowExt 允许的扩展名
     * @return bool
     */
    public function saveFile($uploadForm, $savePath, $allowExt = []) {
        if (empty($allowExt)) {
            $allowExt = Context::$appConfig->allowUploadExt;
        }

	    $savePath = Context::$appBasePath.
		    Context::$appConfig->uploadDir.DIRECTORY_SEPARATOR.$savePath;

	    if (is_array($uploadForm)) {
		    if (!$this->isUploadedFile($uploadForm['tmp_name'])) {
			    return false;
		    }

		    $pathInfo = pathinfo($uploadForm['name']);
		    if (!in_array(strtolower($pathInfo['extension']), $allowExt)) {
			    return false;
		    }

		    if (!movefile($savePath, $uploadForm['tmp_name'])) {
			    return false;
		    }
	    } else {
		    writeover($savePath, $uploadForm);
	    }

        return $savePath;
    }
}

Class UploadFileCannotAccess extends Exception{

}

Class UploadFileExtensionError extends Exception {

	/**
	 * @param string $nowFileExtension
	 * @param array $allowExtensions
	 */
	public function __construct($nowFileExtension, $allowExtensions = []) {

	}

}