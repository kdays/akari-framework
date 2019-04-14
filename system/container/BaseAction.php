<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:27
 */

namespace Akari\system\container;


use Akari\system\ioc\Injectable;
use Akari\system\util\helper\AppResultTrait;
use Akari\system\util\helper\AppValueTrait;

abstract class BaseAction extends Injectable {

    use AppResultTrait, AppValueTrait;

}
