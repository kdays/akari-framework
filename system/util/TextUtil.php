<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 22:45
 */

namespace Akari\system\util;

use Akari\system\security\FilterFactory;
use Akari\system\security\Random;

class TextUtil {

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
            throw new \RuntimeException("please install MB_STRING");
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
                elseif ($val < 0x800)
                    $ret .= chr(0xc0 | ($val >> 6)) .
                        chr(0x80 | ($val & 0x3f));
                else
                    $ret .= chr(0xe0 | ($val >> 12)) .
                        chr(0x80 | (($val >> 6) & 0x3f)) .
                        chr(0x80 | ($val & 0x3f));
                $i += 5;
            } elseif ($str[ $i ] == '%') {
                $ret .= urldecode(substr($str, $i, 3));
                $i += 2;
            } else
                $ret .= $str[ $i ];
        }

        return $ret;
    }

    public static function cutWithDot($str, $length, $splitAdd = '...', $charset = 'utf-8') {
        return mb_strlen($str, $charset) > $length ? mb_substr($str, 0, $length, $charset) . $splitAdd : $str;
    }

    public static function removeStartWhiteSpace($str) {
        $re = implode("", ["\n", "\r", "\x0b", "\t", "\x20", "\0"]);
        $str = trim($str, $re);

        while (TRUE) { //跨区域字符单独处理
            if (mb_substr($str, -1) == "\xe3\x80\x80") {
                $str = mb_substr($str, 0, -1);
            } elseif (mb_substr($str, 1) == "\xe3\x80\x80") {
                $str = mb_substr($str, 1);
            } else {
                break;
            }
        }

        return $str;
    }

    public static function random($length) {
        return Random::hex($length);
    }

    public static function formatFriendlySize(int $size, int $dec = 2) {
        $a = ["B", "KB", "MB", "GB", "TB", "PB"];
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }

        return round($size, $dec) . " " . $a[$pos];
    }

    public static function getFileExtension(string $fn) {
        $ext = ArrayUtil::last( explode(".", $fn) );

        return strtolower($ext);
    }

    public static function exists(string $string, $findme) {
        if (!is_array($findme)) $findme = [$findme];
        $findme = array_filter($findme);

        foreach($findme as $find){
            if(strpos($string, $find) !== FALSE) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * UTF-8切割文字
     *
     * @param string $str 文字内容
     * @param int $start 开始点
     * @param int $length 大小
     * @return string
     */
    public static function subchar($str, $start, $length) {
        if (strlen($str) <= $length) {
            return $str;
        }
        $re = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        preg_match_all($re, $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));

        return $slice;
    }

    /**
     * 将文字分块
     *
     * @param string $content 内容
     * @param int $n 开始点
     * @param int $blockLength 块大小
     * @return array
     */
    public static function cutBlock($content, $n = 0, $blockLength = 30) {
        $arr = array();
        $len = strlen($content);
        for ($i = $n; $i < $len; $i += $blockLength) {
            $res = self::subchar($content, $i, $blockLength);

            if (!empty($res)) {
                $arr[] = $res;
            }
        }

        return $arr;
    }

    public static function parseArgvParams($args) {
        function resolve(array $params) {
            $now = [];
            foreach ($params as $param) {
                if (preg_match('/^--(\w+)(=(.*))?$/', $param, $matches)) {
                    $name = $matches[1];
                    $now[$name] = isset($matches[3]) ? $matches[3] : TRUE;
                } else {
                    $now[] = $param;
                }
            }

            return $now;
        }

        return resolve($args);
    }

}
