<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 15:24.
 */

namespace Akari\utility;

use Akari\system\ioc\DIHelper;

class I18n
{
    use DIHelper;

    /**
     * @return \Akari\system\i18n\I18n
     */
    protected static function getHandler()
    {
        return self::_getDI()->getShared('lang');
    }

    public static function loadPackage($packageId, $prefix = '', $useRelative = true)
    {
        return self::getHandler()->loadPackage($packageId, $prefix, $useRelative);
    }

    public static function has($id, $prefix = '')
    {
        return self::getHandler()->has($id, $prefix);
    }

    public static function get($id, $L = [], $prefix = '')
    {
        return self::getHandler()->get($id, $L, $prefix);
    }
}
