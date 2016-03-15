<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/15
 * Time: 上午10:47
 */

namespace Akari\system\security\filter;

/**
 * Class PureFilter
 * 去除一切标签和危险字符的过滤器
 * 
 * @package Akari\system\security\filter
 */
class PureFilter extends BaseFilter{

    /**
     * 过滤器实现方法
     *
     * @param mixed $data
     * @return mixed
     */
    public function filter($data) {
        if (is_array($data)) {
            return filter_var_array($data, FILTER_SANITIZE_STRING);
        }

        return filter_var($data, FILTER_SANITIZE_STRING);
    }
    
}