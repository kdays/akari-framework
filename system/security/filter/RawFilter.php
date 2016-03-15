<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/15
 * Time: 上午10:11
 */

namespace Akari\system\security\filter;

/**
 * Class RawFilter
 * 不过滤数据 直接通过
 * 
 * @package Akari\system\security\filter
 */
class RawFilter extends BaseFilter{

    /**
     * 过滤器实现方法
     *
     * @param mixed $data
     * @return mixed
     */
    public function filter($data) {
        return $data;
    }
    
}