<?php
namespace Akari\command;

use Akari\system\cache\Cache;
use Akari\system\container\BaseTask;

class CacheValueCommand extends BaseTask {

    public static $command = 'cache';
    public static $description = "设置或获得某条缓存记录";

    public function handle($params) {
        if (empty($params[0])) {
            $this->msg("<info>no cache key input</info>");
            return false;
        }

        $key = $params[0];
        $isRaw = ($params['raw'] ?? 1) == 1;
        $config = $params['config'] ?? 'default';

        if (isset($params['value'])) {
            $timeout = isset($params['timeout']) ? intval($params['timeout']) : NULL;

            Cache::set($key, $params['value'], $timeout, $isRaw, $config);
            $this->msg("<info>$key set</info>");

            return false;
        }

        if (Cache::exists($key, $config)) {
            var_dump(Cache::get($key, $isRaw, $config));
        } else {
            $this->msg("<info>$key not exists on {$config}.</info>");
        }
    }

}