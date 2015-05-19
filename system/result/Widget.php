<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/5/19
 * Time: 下午10:04
 */

namespace Akari\system\result;

use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\ValueHelper;

class Widget {

    use ResultHelper, ValueHelper, Logging;

    /**
     * @param null $userData
     * @return array
     */
    public function execute($userData = NULL) {
        return [];
    }

}