<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:50
 */

namespace Akari\system\util\helper;

use Akari\Core;
use Akari\system\util\AppValueMgr;

trait AppValueTrait {

    protected function _getValue(string $key, $defaultValue = NULL) {
        return AppValueMgr::get($key, $defaultValue);
    }

    protected function _setValue(string $key, $value) {
        return AppValueMgr::set($key, $value);
    }

    protected function _getConfigValue(string $key, $defaultValue) {
        return Core::env($key, $defaultValue);
    }

}
