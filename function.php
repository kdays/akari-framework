<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 19:14
 */

use Akari\Context;
use Akari\utility\helper\ResultHelper;
use Akari\utility\I18n;

/**
 * 检查字符串中是否有某个字符
 *
 * @param string $string 字符串
 * @param mixed $findme 要查找的字符，支持传入数组
 * @return boolean
 */
function in_string($string,$findme){
    !is_array($findme) && $findme = Array($findme);
    foreach($findme as $value){
        if($value == '')	continue;
        if(strpos($string, $value) === false) {
            continue;
        } else {
            return true;
        }
    }
    return false;
}

/**
 * 根据数据获得值
 *
 * @param string $key 关键词
 * @param string $method 获得类型(G=GET P=POST GP=GET&POST)
 * @param string $defaultValue 默认值
 * @return string|NULL
 *
 * @todo key为U.开头时，调用DataHelper，主要是为了方便URL重写的参数处理
 */
function GP($key, $method = 'GP', $defaultValue = NULL){
    $header = substr($key, 0, 2);
    $t = substr($key, 2);

    if ($header == 'U.') {
        if ($method != 'GP' || $method === TRUE) {
            return \Akari\utility\DataHelper::set($t, $method);
        } 
        
        return \Akari\utility\DataHelper::get($t, FALSE, $defaultValue);
    } elseif ($header == 'P.') {
        return GP($t, 'P');
    } elseif ($header == 'G.') {
        return GP($t, 'G');
    }

    if ($method != 'P' && isset($_GET[$key])) {
        return \Akari\utility\TextHelper::filter($_GET[$key]);
    } elseif ($method != 'G' && isset($_POST[$key])) {
        return \Akari\utility\TextHelper::filter($_POST[$key]);
    }

    return $defaultValue;
}

function L($key, $L = []) {
    return I18n::get($key, $L);
}

function view($bindArr, $tplName = NULL, $layoutName = NULL) {
    return ResultHelper::_genTplResult($bindArr, $tplName, $layoutName);
}

/**
 * 载入APP目录的数据
 *
 * @param string $path 路径
 * @param boolean $once 是否仅载入1次
 * @param array $params
 * @throws Exception
 * @return mixed
 * @todo: .会替换成目录中的/
 */
function import($path, $once = TRUE, $params = []){
    static $loadedPath = array();

    $name = explode(".", $path);
    $head = array_shift($name);

    if ($head == "core") {
        $path = AKARI_PATH. implode(DIRECTORY_SEPARATOR, $name). ".php";
    } else {
        $path = Context::$appBasePath. DIRECTORY_SEPARATOR. $head. DIRECTORY_SEPARATOR. implode(DIRECTORY_SEPARATOR, $name). ".php";
    }
    $path = str_replace("#", ".", $path);

    extract($params);

    if(!file_exists($path)){
        throw new Exception("$path not load");
    }else{
        if(!in_array($path, $loadedPath) || !$once){
            $loadedPath[] = $path;
            return require($path);
        }
    }
}

function import_exists($path) {
    $name = explode(".", $path);
    $head = array_shift($name);

    if ($head == "core") {
        $path = AKARI_PATH. implode(DIRECTORY_SEPARATOR, $name). ".php";
    } else {
        $path = Context::$appBasePath. DIRECTORY_SEPARATOR. $head. DIRECTORY_SEPARATOR. implode(DIRECTORY_SEPARATOR, $name). ".php";
    }
    $path = str_replace("#", ".", $path);

    return !!file_exists($path);
}

function cookie($key, $value = NULL, $expire = NULL, $useEncrypt = FALSE) {
    $cookie = \Akari\system\http\Cookie::getInstance();

    if ($value == NULL) {
        return $cookie->get($key);
    }

    $cookie->set($key, $value, $expire, $useEncrypt);
}

function array_flat($list, $columnKey, $indexKey = NULL, $multi = FALSE, $allowObject = FALSE) {
    $result = [];

    foreach ($list as $value) {
        $colValue = (is_array($value) || !$allowObject) ? $value[$columnKey] : $value->$columnKey;

        if ($indexKey !== NULL) {
            $colKey = (is_array($value) || !$allowObject) ? $value[$indexKey] : $value->$indexKey;
            if ($multi) {
                $result[$colKey][] = $colValue;
            } else {
                $result[$colKey] = $colValue;
            }
        } else {
            $result[] = $colValue;
        }
    }

    return $result;
}

function array_index($list, $indexKey, $allowObject = false) {
    if (!is_array($list) && !is_object($list)) {
        return [];
    }

    $result = [];
    foreach ($list as $v) {
        $result[(is_array($v) || !$allowObject) ? $v[$indexKey] : $v->$indexKey] = $v;
    }

    return $result;
}

function array_reindex($list, array $index) {
    $result = [];
    foreach ($index as $k) {
        $result[] = $list[$k];
    }

    return $result;
}

function make_url($url, array $params) {
    return $url. (in_string($url, '?') ? "&" : "?"). http_build_query($params);
}

/**
 * json_decode 优化版
 *
 * @param string $json Json语句
 * @param bool $assoc false返回Object true为Array
 * @return mixed
 */
function json_decode_nice($json, $assoc = TRUE){
    $json = str_replace(array("\n", "\r"), "\n", $json);
    $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/','$1"$3":', $json);
    $json = preg_replace('/(,)\s*}$/', '}', $json);

    return json_decode($json, $assoc);
}

function get_date($format, $timestamp = TIMESTAMP) {
    if (!is_numeric($timestamp))    $timestamp = strtotime($timestamp);
    return date($format, $timestamp);
}