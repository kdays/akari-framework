<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 21:28
 */

namespace Akari\system\cache;

use Akari\Context;

abstract class ICache {

    public $handler;
    protected $options = [];

    public function getOption($key, $itemKey = 'default', $defaultOpts = []) {
        $config = Context::$appConfig->cache;

        if (isset($config[$key])) {
            $data = [];
            if (!is_array(current($config[$key]))) {
                $data = $config[$key];
            }

            if (is_array($config[$key][$itemKey])) {
                $data = $config[$key][$itemKey];
            }

            foreach ($defaultOpts as $k => $v) {
                if (!isset($data[$k])) {
                    $data[$k] = $v;
                }
            }

            return $data;
        }

        return $defaultOpts;
    }

}