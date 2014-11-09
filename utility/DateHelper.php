<?php
namespace Akari\utility;

Class DateHelper {
    public static function toWord($oldTime) {
        $seconds = TIMESTAMP - $oldTime;

        $times = '';
        $days = floor($seconds/86400);
        $hours = floor(($seconds/3600)%24);
        $minutes = floor(($seconds/60)%60);
        $seconds = floor($seconds%60);

        if($seconds >= 1) $times = $seconds.'秒';
        if($minutes >= 1) $times = $minutes.'分钟 '.$times;
        if($hours >= 1) $times = $hours.'小时 '.$times;
        if($days >= 1)  $times = $days.'天';
        if($days > 30) {
	        return get_date('Y-m-d', $oldTime);
        }
        $times .= '前';

        return str_replace(" ", '', $times);
    }

    public static function format($format, $time = NULL) {
        $time = $time == NULL ? TIMESTAMP : $time;
        $time += 3600 * 8;
        return date($format, $time);
    }
}