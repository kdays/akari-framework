<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-04-03
 * Time: 13:00
 */

namespace Akari\system\util;


class DateUtil {

    public static function friendlyTime($unixTime, string $overFormat = 'Y-m-d H:i') {
        if (!is_numeric($unixTime)) $unixTime = strtotime($unixTime);
        if ($unixTime == TIMESTAMP) return L('df.now');

        $now = new \DateTime();
        $last = self::getDateTime($unixTime, FALSE);

        $diff = $now->diff($last);

        if ($diff->y < 1 && $diff->m < 1) {
            $formats = ['d', 'h', 'i', 's'];
            foreach ($formats as $format) {
                if ($diff->$format > 0) {
                    return L('df.' . $format, ['d' => $diff->$format]);
                }
            }
        }

        return self::format($unixTime, $overFormat);
    }

    public static function getTimeOffset() {
        return env('timeOffset', 0);
    }

    public static function getDateTime($unixTime, $addOffset = FALSE) {
        if (!is_numeric($unixTime)) $unixTime = strtotime($unixTime);

        if ($addOffset) $unixTime += self::getTimeOffset();
        return new \DateTime(date('Y-m-d H:i:s', $unixTime));
    }

    public static function format($unixTime, string $format) {
        if (empty($unixTime)) return '-';
        if (!is_numeric($unixTime)) $unixTime = strtotime($unixTime);
        $unixTime += self::getTimeOffset();

        return date($format, $unixTime);
    }

}
