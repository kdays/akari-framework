<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/4/20
 * Time: 上午11:20.
 */

namespace Akari\system\tpl;

interface ExternalTemplateUtilInterface
{
    /**
     * 执行.
     *
     * @param array $args
     *
     * @return mixed
     */
    public static function execute($args);
}
