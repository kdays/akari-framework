<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/12/7
 * Time: 下午6:28
 */

namespace Akari\system\storage\handler;


abstract class BaseStorageHandler {

    protected $config;
    public function __construct(array $config) {
        $this->config = $config;
    }

}