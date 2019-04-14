<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 23:22
 */

namespace Akari\system\security\filter;


use Akari\system\util\TextUtil;

class StrFilter extends BaseFilter{

    /**
     * 过滤器实现方法
     *
     * @param mixed $data
     * @return mixed
     */
    public function filter($data) {
        return TextUtil::filter($data);
    }

}
