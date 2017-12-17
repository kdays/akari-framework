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
        if ($timestamp == TIMESTAMP)    return L('df.now');
        if (!is_numeric($timestamp))    $timestamp  = get_timestamp($timestamp);

        $now = new \DateTime();
        $lastDate = date("Y-m-d H:i:s", $timestamp);
        $last = new \DateTime($lastDate);

        $diff = $now->diff($last);

        if ($diff->y < 1 && $diff->m < 1) {
            $formats = ['d', 'h', 'i', 's'];
            foreach ($formats as $format) {
                if ($diff->$format > 0) {
                    return L('df.' . $format, ['d' => $diff->$format]);
                }
            }
        }

        return $last->format($overFormat);
    }

    public static function format($timestamp, $format = 'Y-m-d H:i:s') {
        if (empty($timestamp))  return '-';

        return get_date($format, $timestamp);
    }

}
