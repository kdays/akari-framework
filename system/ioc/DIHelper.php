<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/4
 * Time: 下午8:40.
 */

namespace Akari\system\ioc;

trait DIHelper
{
    /**
     * @return DI|null
     */
    public static function _getDI()
    {
        return DI::getDefault();
    }
}
