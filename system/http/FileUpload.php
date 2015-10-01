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

    public function getExtension() {
        $ext = end(explode('.', $this->upload['name']));
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

    public function save($target) {
        $savePath = Context::$appBasePath. Context::$appConfig->uploadDir.DIRECTORY_SEPARATOR. $target;
        return FileHelper::moveFile($savePath, $this->getTempPath());
    }
}