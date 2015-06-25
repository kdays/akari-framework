<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 19:14
 */

use Akari\Context;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\I18n;

/**
 * 读取文件
 *
 * @param string $filename 文件路径
 * @param string $method 读取方式
 * @return string
 */
function readover($filename, $method = 'rb'){
    if(function_exists("file_get_contents")){
        return file_get_contents($filename);
    }else{
        $data = '';
        if ($handle = @fopen($filename,$method)) {
            flock($handle,LOCK_SH);
            $data = @fread($handle,filesize($filename));
            fclose($handle);
        }

        return $data;
    }
}

/**
 * 写入文件
 *
 * @param string $fileName 文件路径
 * @param string $data 数据
 * @param string $method 写入方式
 * @param bool $ifChmod 是否权限进行777设定
 */
function writeover($fileName, $data, $method = 'rb+', $ifChmod = true){
    $baseDir = dirname($fileName);
    createdir($baseDir);

    touch($fileName);
    $handle = fopen($fileName, $method);
    flock($handle, LOCK_EX);
    fwrite($handle, $data);
    $method == 'rb+' && ftruncate($handle, strlen($data));
    fclose($handle);
    $ifChmod && @chmod($fileName, 0777);
}

/**
 * 创建目录
 *
 * @param string $path 路径
 * @param boolean $index 是否自动创建index.html文件
 */
function createdir($path, $index = false){
    if(is_dir($path))	return ;
    createdir(dirname($path), $index);

    @mkdir($path);
    @chmod($path,0777);
    if(!$index){
        @fclose(@fopen($path.'/index.html','w'));
        @chmod($path.'/index.html',0777);
    }
}

/**
 * 删除目录
 *
 * @param string $path 路径
 * @return boolean
 */
function deletedir($path){
    if(!is_dir($path))  return false;

    if(rmdir($path) == false){
        if(!$dp = opendir($path))   return false;

        while(($fp = readdir($dp)) !== false){
            if($fp == "." || $fp == "..")   continue;
            if(is_dir("$path/$fp")){
                deletedir("$path/$fp");
            }else{
                unlink("$path/$fp");
            }
        }

        closedir($dp);
        rmdir($path);
    }
}

/**
 * 移动文件
 *
 * @param string $dstfile 目标路径
 * @param string $srcfile 来源路径
 * @return boolean
 */
function movefile($dstfile, $srcfile){
    createdir(dirname($dstfile));
    if (rename($srcfile,$dstfile)) {
        @chmod($dstfile,0777);
        return true;
    } elseif (@copy($srcfile,$dstfile)) {
        @chmod($dstfile,0777);
        unlink($srcfile);
        return true;
    } elseif (is_readable($srcfile)) {
        writeover($dstfile,readover($srcfile));
        if (file_exists($dstfile)) {
            @chmod($dstfile,0777);
            unlink($srcfile);
            return true;
        }
    }
    return false;
}


function C($key, $value = NULL) {
    if ($value === NULL) {
        return Context::$appConfig->$key;
    }

    Context::$appConfig->$key = $value;
}

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
 * 字符串过滤
 *
 * @param string $mixed 数据
 * @param bool|string $isint 是否为int
 * @param bool|string $istrim 是否进行trim
 * @return mixed
 */
function Char_cv($mixed,$isint=false,$istrim=false) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = Char_cv($value,$isint,$istrim);
        }
    } elseif ($isint) {
        $mixed = (int)$mixed;
    } elseif (!is_numeric($mixed) && ($istrim ? $mixed = trim($mixed) : $mixed) && $mixed) {
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
        } else {
            return \Akari\utility\DataHelper::get($t, FALSE, $defaultValue);
        }
    } elseif ($header == 'P.') {
        return GP($t, 'P');
    } elseif ($header == 'G.') {
        return GP($t, 'G');
    }

    if ($method != 'P' && isset($_GET[$key])) {
        return Char_cv($_GET[$key], false, true);
    } elseif ($method != 'G' && isset($_POST[$key])) {
        return Char_cv($_POST[$key], false, true);
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
        Logging::_logErr("Import not found: $path");
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

function cache($key, $value = NULL, $expired = -1, $confId = 'default', $drvName = NULL) {
    $config = Context::$appConfig->cache;
    $drvName = empty($drvName) ? $config['default'] : $drvName;

    !is_numeric($expired) && $expired = strtotime($expired);

    $cacheDriver = implode(NAMESPACE_SEPARATOR, ["Akari", "system", "cache", ucfirst($drvName). "Cache"]);
    $device = new $cacheDriver($confId);

    if ($value === NULL && $expired === FALSE) {
        return $device->remove($key);
    }

    if ($value === NULL) {
        return $device->get($key);
    }

    return $device->set($key, $value, $expired);
}

function cookie($key, $value = NULL, $expire = NULL, $encrypt = FALSE) {
    $cookie = \Akari\system\http\Cookie::getInstance();

    if ($value == NULL) {
        return $cookie->get($key);
    }

    $cookie->set($key, $value, $expire, $encrypt);
}

function array_flat($list, $key) {
    if (!is_array($list)) {
        return FALSE;
    }

    $result = [];
    foreach ($list as $v) {
        if (is_array($v)) {
            $result[] = $v[$key];
        } else {
            $result[] = $v->$key;
        }
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

function snake_case($in) {
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

function camel_case($in) {
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

function get_date($format, $timestamp = TIMESTAMP) {
    return date($format, $timestamp);
}