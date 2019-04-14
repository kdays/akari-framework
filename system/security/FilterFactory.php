<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 23:26
 */

namespace Akari\system\security;

use Akari\Core;
use Akari\system\security\filter\BaseFilter;

class FilterFactory {

    protected static $filters = [];

    public static function doFilter($data, $filterName) {
        return self::getFilter($filterName)->filter($data);
    }

    /**
     * 获得过滤器实例
     *
     * @param string $filterName
     * @return BaseFilter
     */
    public static function getFilter($filterName) {
        if (!isset(self::$filters[$filterName])) {
            $clsName = implode(NAMESPACE_SEPARATOR, ['Akari', 'system', 'security', 'filter', ucfirst($filterName) . 'Filter']);
            $filters = Core::env('filters', []);

            if (array_key_exists($filterName, $filters)) {
                $clsName = $filters[$filterName];
            }

            self::$filters[$filterName] = new $clsName();
        }

        return self::$filters[$filterName];
    }

}
