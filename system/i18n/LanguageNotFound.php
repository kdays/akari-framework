<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 17/1/25
 * Time: 下午5:01
 */

namespace Akari\system\i18n;

use Akari\system\exception\AkariException;

class LanguageNotFound extends AkariException {


    public function __construct($packageId) {
        $this->message = "Language package not found: " . $packageId;
    }

}
