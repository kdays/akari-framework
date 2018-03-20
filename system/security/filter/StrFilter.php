<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2018/3/20
 * Time: 下午3:44
 */

namespace Akari\system\security\filter;

use Akari\utility\TextHelper;

class StrFilter extends BaseFilter{

    /**
     * 过滤器实现方法
     *
     * @param mixed $data
     * @return mixed
     */
    public function filter($data) {
        return TextHelper::filter($data);
    }


}
