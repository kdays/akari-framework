<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-08
 * Time: 14:06
 */

namespace Akari\system\security;

use Akari\system\ioc\Injectable;
use Akari\system\util\helper\AppValueTrait;
use Akari\system\util\helper\AppResultTrait;

abstract class BaseExceptionProcessor extends Injectable {

    use AppResultTrait, AppValueTrait;

    abstract public function process(\Throwable $ex);

}
