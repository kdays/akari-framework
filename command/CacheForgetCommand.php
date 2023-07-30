<?php

namespace Akari\command;

use Akari\system\container\BaseTask;

class CacheForgetCommand extends BaseTask {

    public static $command = 'cache:forget';
    public static $description = "删除某条缓存记录";

    public function handle($params) {
        var_dump($params);

    }

}