<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/19
 * Time: 21:24
 */

namespace Akari\system\http;


use Akari\exception\UploadError;
use Akari\system\storage\Storage;
use Akari\system\ioc\Injectable;
use Akari\system\util\TextUtil;

class FileUpload extends Injectable {

    protected $upload;
    protected $formName;

    public function __construct(array $form, string $formName) {
        $this->upload = $form;
        $this->formName = $formName;
    }

    public static function initFromRaw(string $rawData, string $fileName) {
        return new self([
            'name' => $fileName,
            'data' => $rawData
        ], '__RAW');
    }

    /**
     * @param bool $withoutIdx 只对多维上传文件数组有影响 设置为TRUE时返回不带类似.1的序号
     * @return mixed
     */
    public function getName($withoutIdx = FALSE) {
        if ($withoutIdx && isset($this->upload['multiBase'])) {
            return $this->upload['multiBase'];
        }

        return $this->formName;
    }

    public function getForm() {
        return $this->upload;
    }

    public function makeMD5() {
        if (array_key_exists("data", $this->upload)) {
            return md5($this->upload['data']);
        }
        return md5_file( $this->getTempPath() );
    }

    protected function getPureName() {
        return explode(".", $this->upload['name']);
    }

    public function getExtension() {
        return TextUtil::getFileExtension( $this->getFileName() );
    }

    public function getFileName() {
        return $this->upload['name'];
    }

    public function getSize() {
        if (array_key_exists('data', $this->upload)) {
            return strlen($this->upload['data']);
        }
        return $this->upload['size'];
    }

    protected function getTempPath() {
        if (array_key_exists("data", $this->upload) && !array_key_exists("tmp_name", $this->upload)) {
            $this->upload['tmp_name'] = tempnam(sys_get_temp_dir(), 'ak');
            file_put_contents($this->upload['tmp_name'], $this->upload['data']);
        }
        return $this->upload['tmp_name'];
    }

    public function getResource() {
        return fopen($this->getTempPath(), 'rb');
    }

    public function getFriendlySize($dec = 2) {
        return TextUtil::formatFriendlySize($this->getSize(), $dec);
    }

    public function hasError() {
        if (!array_key_exists("data", $this->upload) && !is_uploaded_file($this->getTempPath())) {
            return TRUE;
        }
        return $this->getError() != UPLOAD_ERR_OK;
    }

    public function getError() {
        return $this->upload['error'] ?? UPLOAD_ERR_OK;
    }

    public function getErrorMessage() {
        $code = $this->getError();

        return $this->lang->get("upload_err." . $code);
    }

    public function isImage() {
        $allowExt = ['png', 'jpg', 'gif', 'jpeg'];
        if (!in_array($this->getExtension(), $allowExt)) {
            return FALSE;
        }

        if (array_key_exists("data", $this->upload)) {
            $size = getimagesizefromstring($this->upload['data']);
        } else {
            $size = getimagesize($this->getTempPath());
        }

        return !empty($size);
    }

    public function getTmpAccessPath() {
        return $this->getTempPath();
    }

    public function getIndex() {
        return isset($this->upload['multiKey']) ? $this->upload['multiKey'] : 0;
    }

    public function saveTo(string $target, string $storageName = 'default') {
        if (empty($this->getTempPath())) {
            throw new UploadError($this->getErrorMessage(), $this->getError());
        }

        return Storage::disk($storageName)->put($target, $this->getResource());
    }

    public function __destruct() {
        if (array_key_exists("tmp_name", $this->upload) && array_key_exists("data", $this->upload)) {
            @unlink($this->upload['tmp_name']);
        }
    }

}
