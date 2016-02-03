<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/31
 * Time: 下午9:37
 */

namespace Akari\system\security\cipher;


class CipherUtil {

    public static function pkcs5_pad($text, $blockSize){
        $pad = $blockSize - (strlen($text) % $blockSize);
        return $text . str_repeat(chr($pad), $pad);
    }
    
    public static function pkcs5_unpad($text){
        $pad = ord($text{strlen($text) - 1});
        if ($pad > strlen($text)) {
            return false;
        }
        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) {
            return false;
        }
        $ret = substr($text, 0, -1 * $pad);
        return $ret;
    }

    /**
     * UTF-8切割文字
     *
     * @param string $str 文字内容
     * @param int $start 开始点
     * @param int $length 大小
     * @return string
     */
    public static function subchar($str, $start = 0, $length) {
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
    public static function makeTextBlocks($content, $n = 0, $blockLength = 30) {
        $arr = array ();
        $len = strlen($content);
        for ($i = $n; $i < $len; $i += $blockLength) {
            $res = self::subchar($content, $i, $blockLength);

            if (!empty ($res)) {
                $arr[] = $res;
            }
        }
        return $arr;
    }
    
    public static function long2str($v, $w) {
        $len = count($v);
        $n = ($len - 1) << 2;
        if ($w) {
            $m = $v[$len - 1];
            if (($m < $n - 3) || ($m > $n)) return false;
            $n = $m;
        }
        $s = array();
        for ($i = 0; $i < $len; $i++) {
            $s[$i] = pack("V", $v[$i]);
        }
        if ($w) {
            return substr(join('', $s), 0, $n);
        }
        else {
            return join('', $s);
        }
    }
    
    public static function str2long($s, $w) {
        $v = unpack("V*", $s. str_repeat("\0", (4 - strlen($s) % 4) & 3));
        $v = array_values($v);
        if ($w) {
            $v[count($v)] = strlen($s);
        }
        return $v;
    }
    
    public static function int32($n) {
        while ($n >= 2147483648) $n -= 4294967296;
        while ($n <= -2147483649) $n += 4294967296; 
        return (int)$n;
     }
}