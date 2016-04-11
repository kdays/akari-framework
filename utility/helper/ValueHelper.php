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
     * 获得在DataHelper的数据 不会在模板展现
     *
     * @param string $key
     * @param null|string $subKey
     * @param mixed $defaultValue
     * @return mixed
     */
    protected static function _getValue($key, $subKey = NULL, $defaultValue = NULL) {
        return DataHelper::get($key, $subKey, $defaultValue);
    }
    
    protected static function _hasValue($key, $subKey = NULL) {
        return DataHelper::has($key, $subKey);
    } 

    /**
     * 设置DataHelper的数据，不会在模板展现，模板展现用_bindValue
     *
     * @param string $key
     * @param mixed $data
     */
    protected static function _setValue($key, $data) {
        DataHelper::set($key, $data);
    }

}