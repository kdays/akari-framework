<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/5/19
 * Time: 下午10:04.
 */

namespace Akari\system\result;

use Akari\system\ioc\Injectable;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ValueHelper;

abstract class Widget extends Injectable
{
    use ValueHelper, Logging;

    /**
     * @param mixed $userData
     *
     * @return array
     */
    abstract public function execute($userData = null);
}
