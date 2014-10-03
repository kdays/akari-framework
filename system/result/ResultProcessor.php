<?php
namespace Akari\system\result;

Class ResultProcessor {
    public static $p;
    public static function getInstance() {
        if (!isset(self::$p)) {
            self::$p = new self();
        }

        return self::$p;
    }

    public $handler;

    public function doProcess($result) {
        $this->handler->doProcess($result);
    }

    public function doInit($data) {
        return $this->handler->init($data);
    }

    public function setDefaultResultHandler($cls) {
        $this->handler = new $cls();
    }

}