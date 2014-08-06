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

    public function toWord($oldTime) {
        $seconds = $this->timestamp - $oldTime;

        $times = '';
        $days = floor(($seconds/86400)%30);
        $hours = floor(($seconds/3600)%24);
        $minutes = floor(($seconds/60)%60);
        $seconds = floor($seconds%60);
        if($seconds >= 1) $times .= $seconds.'秒';
        if($minutes >= 1) $times = $minutes.'分钟 '.$times;
        if($hours >= 1) $times = $hours.'小时 '.$times;
        if($days >= 1)  $times = $days.'天';
        if($days > 30) return false;
        $times .= '前';

        return str_replace(" ", '', $times);
    }

    public function format($format) {
        return date($format, $this->timestamp);
    }
}