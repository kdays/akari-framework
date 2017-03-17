<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 17/3/16
 * Time: 下午5:29
 */

namespace Akari\system\security\filter;


use Akari\system\security\SafeHTML;

class HtmlFilter extends BaseFilter {

    /**
     * 过滤器实现方法
     *
     * @param mixed $data
     * @return mixed
     */
    public function filter($data) {
        return SafeHTML::filter($data);
    }
    
}