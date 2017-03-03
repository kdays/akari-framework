<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/27
 * Time: 下午2:19
 */

namespace Akari\system\security;

use Akari\system\exception\AkariException;

class CSRFVerifyFailed extends AkariException {

    private $rightToken;
    private $userToken;

    public function __construct($rightToken, $userToken) {
        $this->message = "[Akari.Security]
            表单验证失败，请返回上一页刷新重新提交试试。
            如果多次失败可以尝试更换游览器再行提交。
            (POST Security Token Verify Failed)";

        $this->rightToken = $rightToken;
        $this->userToken = $userToken;
    }

    public function getRightToken() {
        return $this->rightToken;
    }

    public function getUserToken() {
        return $this->userToken;
    }

}
