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

    const I18N_NAME = 'csrf_verify_error';

    public function __construct($rightToken, $userToken) {
        $this->message = L(self::I18N_NAME);

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
