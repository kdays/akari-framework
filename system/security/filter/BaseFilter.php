<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 23:19
 */

namespace Akari\system\security\filter;


abstract class BaseFilter {

    /**
     * 过滤器实现方法
     *
     * @param mixed $data
     * @return mixed
     */
    abstract public function filter($data);

}