<?php

namespace Akari\command;

use Akari\system\cache\Cache;
use Akari\system\container\BaseTask;

class CacheForgetCommand extends BaseTask {

    public static $command = 'cache:forget';
    public static $description = "删除某条缓存记录";

    public function handle($params) {
        if (empty($params[0])) {
            $this->msg("<info>no cache key input</info>");
            return false;
        }

        $key = $params[0];
        $config = $params['config'] ?? 'default';

        if (Cache::exists($key, $config)) {
            Cache::remove($key, $config);
            $this->msg("<success>$key cache removed</success>");
        } else {
            $this->msg("<info>$key not exists on {$config}.</info>");
        }
    }

}