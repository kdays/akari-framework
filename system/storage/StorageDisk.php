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

    public function __construct(IStorageHandler $handler) {
        $this->handler = $handler;
    }

    public function append(string $path, $content) {
        return $this->handler->append($path, $content);
    }

    public function put(string $path, $content) {
        return $this->handler->put($path, $content);
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

    public function getHandler() {
        return $this->handler;
    }
}
