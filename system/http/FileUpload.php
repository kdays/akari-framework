<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/22
 * Time: 下午8:40
 */

namespace Akari\system\http;

use Akari\system\ioc\Injectable;
use Akari\utility\FileHelper;

class FileUpload extends Injectable {

    protected $upload;
    protected $formName;
    
    public function __construct(array $form, $formId) {
        $this->upload = $form;
        $this->formName = $formId;
    }

    /**
     * @param bool $withoutIdx 只对多维上传文件数组有影响 设置为TRUE时返回不带类似.1的序号
     * @return mixed
     */
    public function getName($withoutIdx = False) {
        if ($withoutIdx && isset($this->upload['multiBase'])) {
            return $this->upload['multiBase'];
        }
        
        return $this->formName;
    }

    public function getForm() {
        return $this->upload;
    }
    
    protected function getNameSection() {
        return explode(".", $this->upload['name']);
    }

    public function getExtension() {
        return FileHelper::getFileExtension($this->getFileName());
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

    public function formatFileSize($dec = 2) {
        return FileHelper::formatFileSize($this->getFileSize(), $dec);
    }
    
    public function hasError() {
        return $this->getError() != UPLOAD_ERR_OK;
    }

    public function getError() {
        return $this->upload['error'];
    }
    
    public function getErrorMessage() {
        $code = $this->getError();
        return $this->lang->get('upload_err.'. $code);
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
    
    public function getIdx() {
        return isset($this->upload['multiKey']) ? $this->upload['multiKey'] : 0;
    }
    
    public function getSavePath($target) {
        return FileHelper::getUploadPath($target);
    }

    public function save($target, $isRelativePath = TRUE) {
        $savePath = $isRelativePath ? $this->getSavePath($target) : $target;
        if (empty($this->getTempPath())) {
            throw new UploadFailed($this->getErrorMessage(), $this->getError());
        }
        
        return FileHelper::moveFile($savePath, $this->getTempPath());
    }
}
