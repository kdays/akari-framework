<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/10/1
 * Time: 上午11:04
 */

namespace Akari\utility;


class TextHelper {

    public static function snakeCase($in) {
        static $upperA = 65;
        static $upperZ = 90;
        $len = strlen($in);
        $positions = [];
        for ($i = 0; $i < $len; $i++) {
            if (ord($in[ $i ]) >= $upperA && ord($in[ $i ]) <= $upperZ) {
                $positions[] = $i;
            }
        }
        $positions = array_reverse($positions);

        foreach ($positions as $pos) {
            $in = substr_replace($in, '_' . lcfirst(substr($in, $pos)), $pos);
        }

        return $in;
    }

    public static function camelCase($in) {
        $positions = [];
        $lastPos = 0;
        while (($lastPos = strpos($in, '_', $lastPos)) !== FALSE) {
            $positions[] = $lastPos;
            $lastPos++;
        }
        $positions = array_reverse($positions);

        foreach ($positions as $pos) {
            $in = substr_replace($in, strtoupper($in[ $pos + 1 ]), $pos, 2);
        }

        return $in;
    }

    public static function filter($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[ $key ] = self::filter($value);
            }
        } elseif (!is_numeric($mixed) && ($mixed = trim($mixed)) && $mixed) {
            $mixed = str_replace(["\0", "%00", "\r"], '', $mixed);
            $mixed = preg_replace(
                ['/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/', '/&(?!(#[0-9]+|[a-z]+);)/is'],
                ['', '&amp;'],
                $mixed
            );
            $mixed = str_replace(["%3C", '<'], '&lt;', $mixed);
            $mixed = str_replace(["%3E", '>'], '&gt;', $mixed);
            $mixed = str_replace(['"', "'", "\t", '  '], ['&quot;', '&#39;', '	', '&nbsp;&nbsp;'], $mixed);
        }

        return $mixed;
    }

    public static function escapeStr($str, $inEncoding = 'UTF-8') {
        if (!function_exists('mb_get_info')) {
            throw new \Exception("need mb_string");
        }

        $return = '';
        for ($x = 0; $x < mb_strlen($str, $inEncoding); $x++) {
            $t = mb_substr($str, $x, 1, $inEncoding);
            if (strlen($t) > 1) { // 多字节字符 
                $return .= '%u' . strtoupper(bin2hex(mb_convert_encoding($t, 'UCS-2', $inEncoding)));
            } else {
                $return .= '%' . strtoupper(bin2hex($t));
            }
        }

        return $return;
    }

    public static function unescapeStr($str) {
        $ret = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            if ($str[ $i ] == '%' && $str[ $i + 1 ] == 'u') {
                $val = hexdec(substr($str, $i + 2, 4));
                if ($val < 0x7f)
                    $ret .= chr($val);
                else
                    if ($val < 0x800)
                        $ret .= chr(0xc0 | ($val >> 6)) .
                            chr(0x80 | ($val & 0x3f));
                    else
                        $ret .= chr(0xe0 | ($val >> 12)) .
                            chr(0x80 | (($val >> 6) & 0x3f)) .
                            chr(0x80 | ($val & 0x3f));
                $i += 5;
            } else
                if ($str[ $i ] == '%') {
                    $ret .= urldecode(substr($str, $i, 3));
                    $i += 2;
                } else
                    $ret .= $str[ $i ];
        }

        return $ret;
    }
    
    public static function cutStr($str, $length, $splitAdd = '...') {
        return mb_strlen($str) > $length ? mb_substr($str, 0, $length). $splitAdd : $length;
    }

}