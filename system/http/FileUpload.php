<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/22
 * Time: 下午8:40
 */

namespace Akari\system\http;


use Akari\Context;
use Akari\utility\FileHelper;
use Akari\utility\UploadHelper;

class FileUpload {

    protected $upload;
    protected $id;
    
    private $codeMessage = [
        UPLOAD_ERR_INI_SIZE => "文件大小超过系统配置",
        UPLOAD_ERR_FORM_SIZE => "文件大小超过表单配置",
        UPLOAD_ERR_PARTIAL => "文件只有部分被上传",
        UPLOAD_ERR_NO_FILE => "没有文件被上传",
        UPLOAD_ERR_NO_TMP_DIR => "系统错误,没有找到临时文件夹",
        UPLOAD_ERR_CANT_WRITE => "系统错误,文件写入失败"
    ];

    public function __construct(array $form, $formId) {
        $this->upload = $form;
        $this->id = $formId;
    }

    public function getName() {
        return $this->id;
    }

    public function getForm() {
        return $this->upload;
    }
    
    protected function getNameSection() {
        return explode(".", $this->upload['name']);
    }

    public function getExtension() {
        $ext = end($this->getNameSection());
        return strtolower($ext);
    }

    public function isUploadedFile() {
        return is_uploaded_file($this->upload['tmp_name']);
    }

    public function getFileName() {
        return $this->upload['name'];
    }

    public function getFileSize() {
        return $this->upload['size'];
    }

    public function getTempPath() {
        return $this->upload['tmp_name'];
    }

    public function formatFileSize() {
        return UploadHelper::formatFileSize($this->getFileSize());
    }

    public function getError() {
        return $this->upload['error'];
    }

    public function isImage() {
        $allowExt = ['png', 'jpg', 'gif', 'jpeg'];
        if (!in_array($this->getExtension(), $allowExt)) {
            return False;
        }

        if (!getimagesize($this->getTempPath())) {
            return False;
        }

        return True;
    }
    
    public function getSavePath($target) {
        if (is_callable(Context::$appConfig->uploadDir)) {
            return Context::$appConfig->uploadDir($target, TRUE);
        }
        
        return Context::$appBasePath. Context::$appConfig->uploadDir.DIRECTORY_SEPARATOR. $target;   
    }

    public function save($target) {
        $savePath = $this->getSavePath($target);
        if (empty($this->getTempPath())) {
            throw new UploadFailed($this->codeMessage[$this->getError()], $this->getError());
        }
        return FileHelper::moveFile($savePath, $this->getTempPath());
    }
}

Class UploadFailed extends \Exception {
    
    public function __construct($message, $code) {
        $this->message = "文件上传失败, 原因:" . $message;
        $this->code = $code;
    }

}