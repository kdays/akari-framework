<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 19:14
 */
use Akari\Context;
use Akari\utility\I18n;
use Akari\utility\ApplicationDataMgr;
use Akari\utility\helper\ResultHelper;
use Akari\system\security\FilterFactory;

/**
 * 检查字符串中是否有某个字符
 *
 * @param string $string 字符串
 * @param string|array $findme 要查找的字符，支持传入数组
 * @return boolean
 */
function in_string($string, $findme) {
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
 * 根据数据获得值
 *
 * @param string $key 关键词
 * @param string $method 获得类型(G=GET P=POST GP=GET&POST)
 * @param string $defaultValue 默认值
 * @param string $filter 过滤器
 * @return mixed
 * 
 * @todo key为U.开头时，调用DataHelper，主要是为了方便URL重写的参数处理
 */
function GP($key, $method = 'GP', $defaultValue = NULL, $filter = "default") {
    if (substr($key, 0, 2) == 'U.') {
        $t = substr($key, 2);
        if ($method != 'GP' || $method === TRUE) {
            ApplicationDataMgr::set($t, $method);

            return NULL;
        } 

        return ApplicationDataMgr::get($t, FALSE, $defaultValue);
    }

    if ($method != 'P' && isset($_GET[$key])) {
        return FilterFactory::doFilter($_GET[$key], $filter);
    } elseif ($method != 'G' && isset($_POST[$key])) {
        return FilterFactory::doFilter($_POST[$key], $filter);
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
 * @param string $library 路径
 * @param boolean $once 是否仅载入1次
 * @param array $params
 * @throws Exception
 * @return mixed
 * @todo: .会替换成目录中的/
 */
function import($library, $once = TRUE, $params = []) {
    static $loadedPath = array();

    $filePath = import_exists($library, TRUE);
    extract($params);
    if(!file_exists($filePath)){
        throw new \Akari\system\exception\AkariException("Import Failed: $library");
    }

    if(!in_array($filePath, $loadedPath) || !$once){
        $loadedPath[] = $filePath;

        return require $filePath;
    }
}

function import_exists($library, $returnPath = FALSE) {
    $name = explode(".", $library);
    $head = array_shift($name);

    $filePath = NULL;
    $basePaths = Context::$nsPaths;
    $basePaths['core'] = AKARI_PATH;
    if (isset($basePaths[$head])) {
        $filePath = $basePaths[$head] . implode(DIRECTORY_SEPARATOR, $name) . ".php";
    } else {
        $filePath = implode(DIRECTORY_SEPARATOR, array_merge([Context::$appBasePath, $head], $name)) . ".php";
    }
    $filePath = str_replace("#", ".", $filePath);

    return $returnPath ? $filePath : !!file_exists($filePath);
}

function cookie($key, $value = NULL, $expire = NULL, $useEncrypt = FALSE) {
    /** @var \Akari\system\http\Cookie $cookie */
    $cookie = \Akari\system\ioc\DI::getDefault()->getShared('cookies');

    if ($value == NULL) {
        return $cookie->get($key);
    }
    $cookie->set($key, $value, $expire, $useEncrypt);
}

/**
 * 平行化数组,和array_column相比支持对象的操作
 * 
 * @param array|object $list 
 * @param string $columnKey 内容键
 * @param string|null $indexKey 索引键,NULL时索引按照自增索引
 * @param bool $multi 是否允许重复的索引键
 * @param bool $allowObject 是否始终使用数组方式读取
 * 
 * @return array
 */
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

/**
 * 根据$indexKey为Key生成
 * 
 * @param array|object $list 数组或对象
 * @param string $indexKey 索引键
 * @param bool $allowObject 是否始终使用数组,如果Model有使用ArrayAccess请保持false
 * 
 * @return array
 */
function array_index($list, $indexKey, $allowObject = FALSE) {
    if (!is_array($list) && !is_object($list)) {
        return [];
    }

    $result = [];
    foreach ($list as $v) {
        $result[(is_array($v) || !$allowObject) ? $v[$indexKey] : $v->$indexKey] = $v;
    }

    return $result;
}

/**
 * 按照$index的数组对$list按顺序取值
 * 
 * @param array $list
 * @param array $index
 * @return array
 */
function array_reindex($list, array $index) {
    $result = [];
    foreach ($index as $k) {
        $result[] = $list[$k];
    }

    return $result;
}

function make_url($url, array $params) {
    if (empty($params)) return $url;

    return $url . (in_string($url, '?') ? "&" : "?") . http_build_query($params);
}

/**
 * json_decode 优化版
 *
 * @param string $json Json语句
 * @param bool $assoc false返回Object true为Array
 * @return mixed
 */
function json_decode_nice($json, $assoc = TRUE) {
    $json = str_replace(array("\n", "\r"), "\n", $json);
    $json = preg_replace('/([{,]+)(\s*)([^"]+?)\s*:/', '$1"$3":', $json);
    $json = preg_replace('/(,)\s*}$/', '}', $json);

    return json_decode($json, $assoc);
}

function get_date($format, $timestamp = TIMESTAMP) {
    if ($timestamp == '0000-00-00 00:00:00')    return "";
    if (!is_numeric($timestamp))    $timestamp = strtotime($timestamp);
    if (Context::$appConfig->offsetTime) {
        $timestamp += Context::$appConfig->offsetTime;
    }

    return date($format, $timestamp);
}

function get_timestamp($str) {
    $timestamp = is_numeric($str) ? $str : strtotime($str);
    // timestamp的时候如果有timeZone 也必须指定
    $timestamp -= Context::$appConfig->offsetTime;

    return $timestamp;
}

if (!function_exists("hex2bin")) {
    function hex2bin($hex) {
        return $hex !== FALSE && preg_match('/^[0-9a-fA-F]+$/i', $hex) ? pack("H*", $hex) : FALSE;
    } 
}
