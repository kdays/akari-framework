<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/15
 * Time: 上午10:05.
 */

namespace Akari\system\security;

use Akari\Context;
use Akari\system\security\filter\BaseFilter;

class FilterFactory
{
    protected static $filters = [];

    public static function doFilter($data, $filterName)
    {
        return self::getFilter($filterName)->filter($data);
    }

    /**
     * 获得过滤器实例.
     *
     * @param string $filterName
     *
     * @return BaseFilter
     */
    public static function getFilter($filterName)
    {
        if (!isset(self::$filters[$filterName])) {
            $clsName = implode(NAMESPACE_SEPARATOR, ['Akari', 'system', 'security', 'filter', ucfirst($filterName).'Filter']);
            if (isset(Context::$appConfig->filters[$filterName])) {
                $clsName = Context::$appConfig->filters[$filterName];
            }

            self::$filters[$filterName] = new $clsName();
        }

        return self::$filters[$filterName];
    }
}
