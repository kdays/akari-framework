<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/15
 * Time: 上午10:00.
 */

namespace Akari\system\security\filter;

use Akari\utility\TextHelper;

/**
 * Class StringFilter
 * 默认行为同DefaultFilter.
 */
class StringFilter extends BaseFilter
{
    /**
     * 过滤器实现方法.
     *
     * @param mixed $data
     *
     * @return mixed
     */
    public function filter($data)
    {
        return TextHelper::filter($data);
    }
}
