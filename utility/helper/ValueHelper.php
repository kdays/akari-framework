<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:56
 */

namespace Akari\utility\helper;

use Akari\system\tpl\TemplateHelper;
use Akari\utility\DataHelper;

trait ValueHelper {

    /**
     * 将参数绑定到模板 别的不会执行
     *
     * @param $key
     * @param $value
     * @return array
     */
    public static function _bindValue($key, $value = NULL) {
        TemplateHelper::assign($key, $value);
    }

    /**
     * 获得在DataHelper的数据 不会在模板展现
     *
     * @param string $key
     * @param bool $subKey
     * @param null $defaultValue
     * @return array|null|object
     */
    public static function _getValue($key, $subKey = false, $defaultValue = NULL) {
        return DataHelper::get($key, $subKey, $defaultValue);
    }

    /**
     * @param string $key
     * @param mixed $data
     * @param bool $isOverwrite
     * @return bool
     */
    public static function _setValue($key, $data, $isOverwrite = TRUE) {
        return DataHelper::set($key, $data, $isOverwrite);
    }

}