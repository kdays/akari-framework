<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/19
 * Time: 21:27
 */

namespace Akari\system\storage;


use Akari\system\storage\handler\IStorageHandler;

class StorageDisk {

    protected $handler;

    const PUT_MODE_OVERWRITE = NULL;
    const PUT_MODE_APPEND = 'APPEND';

    public function __construct(IStorageHandler $handler) {
        $this->handler = $handler;
    }

    public function put(string $path, $content, $mode = self::PUT_MODE_OVERWRITE) {
        return $this->handler->put($path, $content, $mode);
    }

    public function get(string $path) {
        return $this->handler->get($path);
    }

    public function delete($path) {
        return $this->handler->delete($path);
    }

    public function exists(string $path) {
        return $this->handler->exists($path);
    }

    public function toUrl(string $path, array $options = []) {
        return $this->handler->toUrl($path, $options);
    }

    public function size(string $path) {
        return $this->handler->size($path);
    }

    public function items(string $path, array $options = []) {
        return $this->handler->items($path, $options);
    }

}
