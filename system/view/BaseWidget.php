<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-18
 * Time: 17:52
 */

namespace Akari\system\view;

use Akari\system\ioc\Injectable;
use Akari\system\util\helper\AppValueTrait;
use Akari\system\util\helper\AppResultTrait;

abstract class BaseWidget extends Injectable {

    use AppResultTrait, AppValueTrait;

    abstract public function handle(array $params) :?array;

}
