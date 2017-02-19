<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/15
 * Time: 上午10:08.
 */

namespace Akari\system\security\filter;

use Akari\utility\TextHelper;

/**
 * Class DefaultFilter
 * 老版框架的实现方法,为了安全,继续实现本方法.
 */
class DefaultFilter extends BaseFilter
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
