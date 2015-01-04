<?php
namespace Akari\system\event;

use Akari\system\result\Result;

/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 08:53
 */

interface Rule {

    /**
     * 处理规则请求器
     *
     * @param Result|NULL $result
     * @return Result
     */
    public function process(Result $result = NULL);

}