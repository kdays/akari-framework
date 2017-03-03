<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/15
 * Time: 上午10:01
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
