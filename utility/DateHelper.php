<?php
namespace Akari\utility;

Class DateHelper {
    public static $h;
    public static function getInstance() {
        if (!isset(self::$h)) {
            self::$h = new self();
        }

        return self::$h;
    }

    public static $GMTTimestamp;
    public $timestamp = 0;

    public static $timeOffset = 0;
    public static $serverTimezoneOffset = 0;

    public static function setTimeOffset($offset) {
        self::$timeOffset = $offset;
        self::$serverTimezoneOffset = idate('Z');
    }

    public function __construct($gmtTime = NULL) {
        if (empty($gmtTime)) {
            $gmtTime = gmmktime();
            self::$GMTTimestamp = $gmtTime;
        }
        $this->timestamp = $gmtTime + (self::$timeOffset - self::$serverTimezoneOffset);
    }

    public function __get($name) {
        if ($name == 'year') {
            return $this->format('Y');
        } elseif ($name == 'month') {
            return $this->format('m');
        } elseif ($name == 'day') {
            return $this->format('d');
        }

        return NULL;
    }

    public function word($oldTime) {
        $diff = $this->timestamp - $oldTime;


    }

    public function format($format) {
        return date($format, $this->timestamp);
    }
}