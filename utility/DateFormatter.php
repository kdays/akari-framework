<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/2
 * Time: 上午10:59
 */

namespace Akari\utility;

class DateFormatter {

    public static function friendlyTime($timestamp, $overFormat = 'Y-m-d H:i') {
        if ($timestamp == TIMESTAMP)    return '刚刚';
        if (empty($timestamp) || $timestamp == '0000-00-00 00:00:00')  return '未知';
        if (!is_numeric($timestamp))    $timestamp  = strtotime($timestamp);

        $now = new \DateTime();
        $lastDate = date("Y-m-d H:i:s", $timestamp);
        $last = new \DateTime($lastDate);

        $diff = $now->diff($last);

        if ($diff->y > 0 || $diff->m > 0) {
            return $last->format($overFormat);    
        }

        if ($diff->d > 0)   return $diff->d . "天前";
        if ($diff->h > 0)   return $diff->h . "小时前";
        if ($diff->i > 0)   return $diff->i . "分钟前";
        if ($diff->s > 0)   return $diff->s . "秒前";

        return $lastDate;
    }

    public static function format($timestamp, $format = 'Y-m-d H:i:s') {
        if (empty($timestamp))  return '-';

        return get_date($format, $timestamp);
    }

}
