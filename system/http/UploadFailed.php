<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/27
 * Time: 下午2:14
 */

namespace Akari\system\http;

use Akari\system\exception\AkariException;

class UploadFailed extends AkariException {

    public function __construct($message, $code) {
        $this->message = "文件上传失败, 原因:" . $message;
        $this->code = $code;
    }

}
