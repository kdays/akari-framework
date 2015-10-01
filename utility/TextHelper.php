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
            if (ord($in[$i]) >= $upperA && ord($in[$i]) <= $upperZ) {
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
        while (($lastPos = strpos($in, '_', $lastPos)) !== false) {
            $positions[] = $lastPos;
            $lastPos++;
        }
        $positions = array_reverse($positions);

        foreach ($positions as $pos) {
            $in = substr_replace($in, strtoupper($in[$pos + 1]), $pos, 2);
        }

        return $in;
    }
    
    public static function filter($mixed) {
        if (is_array($mixed)) {
            foreach ($mixed as $key => $value) {
                $mixed[$key] = self::filter($value);
            }
        } elseif (!is_numeric($mixed) && ($mixed = trim($mixed)) && $mixed) {
            $mixed = str_replace(array("\0","%00","\r"),'',$mixed);
            $mixed = preg_replace(
                array('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/','/&(?!(#[0-9]+|[a-z]+);)/is'),
                array('','&amp;'),
                $mixed
            );
            $mixed = str_replace(array("%3C",'<'),'&lt;',$mixed);
            $mixed = str_replace(array("%3E",'>'),'&gt;',$mixed);
            $mixed = str_replace(array('"',"'","\t",'  '),array('&quot;','&#39;','	','&nbsp;&nbsp;'),$mixed);
        }
        return $mixed;
    }
    
}