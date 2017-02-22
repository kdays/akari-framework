<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 17/2/15
 * Time: 下午12:02
 */

namespace Akari\system\conn;

class DBUtil {

    public static function mergeMetaKeys($keys, DBConnection $DBConnection) {
        $result = [];
        foreach ($keys as $key) {
            $result[] = $DBConnection->getMetaKey($key) . "  = :$key";
        }
        
        return implode(",", $result);
    }
    
    public static function makeLimit($limit) {
        return is_array($limit) ? 
            ' LIMIT '. $limit[0]. ",". $limit[1] : 
            ' LIMIT '. $limit;
    }
    
    public static function getInKeysCond($values, $prefix = 'ALB_') {
        $keys = [];
        foreach ($values as $k => $val) {
            $keys[] = ":". $prefix. $k;
        }
        
        return implode(",", $keys);
    }
    
    public static function getInKeysBindValues($values, $prefix = 'ALB_') {
        $result = [];
        foreach ($values as $k => $val) {
            $result[ $prefix . $k ] = $val;
        }
        
        return $result;
    }
    
}