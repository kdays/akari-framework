<?php
!defined("AKARI_PATH") && exit;

use Akari\Context;
use Akari\utility\I18n;
use Akari\utility\TemplateHelper;

/**
 * 读取文件
 * 
 * @param string $filename 文件路径
 * @param string $method 读取方式
 * @return string|unknown
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
 * @param bool|string $ifChmod 是否权限进行777设定
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
		delfile($srcfile);
		return true;
	} elseif (is_readable($srcfile)) {
		writeover($dstfile,readover($srcfile));
		if (file_exists($dstfile)) {
			@chmod($dstfile,0777);
			delfile($srcfile);
			return true;
		}
	}
	return false;
}


function checkDir($requestPath, $basePath = false){
	if(!$basePath){
		$basePath = realpath(Context::$appBasePath);
	}
	
	$requestPath = realpath($requestPath);
	if(strpos($requestPath, $basePath)){
		return TRUE;
	}
	
	return FALSE;
}

/**
 * 获得缓存实例
 * 
 * @param string $type 缓存类型
 * @throws Excepton
 * @return BaseCacheAdapter
 */
function getCacheInstance($type = "File"){
	static $CacheInstance = Array();
	$type = $type ? $type : Context::$appConfig->defaultCacheType;
	$type = ucfirst($type);

	$cls = "Akari\\system\\data\\".$type."Adapter";
	if(!class_exists($cls)){
		throw new Excepton("[Akari.Cache] Get CacheInstance Error, Not Found [$type]");
	}

	if(!isset($CacheInstance[$type])){
		$CacheInstance[$type] = new $cls();
	}

	return $CacheInstance[$type];
}

/**
 * 缓存数据
 * 
 * @param string $key 缓存键名
 * @param string $value 缓存数据
 * @param int $expired 过期时间
 * @param array $opts 可选
 * @todo 如果value为NULL且expired为FALSE则为删除，只有value为NULL为取值
 */
function cache($key, $value = NULL, $expired = -1, $opts = array()){
	$cls = getCacheInstance(array_key_exists("type", $opts) ? $opts["type"] : false);

	if($value === NULL && $expired === FALSE){
		return $cls->remove($key);
	}

	if(!is_numeric($expired))	$expried = strtotime($expired);

	if($value === NULL){
		return $cls->get($key);
	}
	return $cls->set($key, $value, $expired);
}

/**
 * 计数统计
 *
 * @param string $key 键名
 * @param int|number $value 变更的值
 * @param boolean $cache 是否保存进缓存
 * @return Ambigous <number>
 */
function logcount($key, $value = 0, $cache = false){
	static $logs = array();
	
	if(!isset($logs[$key])){
		$logs[$key] = ($cache !== false) ? cache("C_{$key}") : 0;
	}
	
	if(empty($value)){
		return $logs[$key];
	}else{
		$logs[$key] += (int)$value;
	}
	
	if($cache !== false){
		cache("C_{$key}", $logs[$key], $cache);
	}
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
 * @return Ambigous <number, mixed>
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
 * 根据Model返回单例
 *
 * @param string $ModelName 模型名称
 * @return mixed
 */
function M($ModelName){
	static $tmpModel = array();

	$modelPath = Context::$appBasePath."/app/model/$ModelName.php";
	if(!file_exists($modelPath)){
		$ModelName .= "Model";
		$modelPath = Context::$appBasePath."/app/model/$ModelName.php";
	}

	$modelClass = $ModelName;
	if(strpos($ModelName, "/") !== false){
		$modelClass = trim(basename($ModelName), ".php");
	}

	include_once($modelPath);

	if(method_exists($modelClass, "getInstance")){
		return $modelClass::getInstance();
	}else{
		if(!isset($tmpModel[ $modelClass ])){
			$tmpModel[$modelClass] = new $modelClass();
		}

		return $tmpModel[$modelClass];
	}
}

/**
 * 获得语言
 * 
 * @param string $key 语言Key
 * @param array $L 替换用数组
 * @param string $prefix 前缀
 * @return string
 */
function L($key, $L = Array(), $prefix = ''){
	return I18n::get($key, $L, $prefix);
}

/**
 * 获得设置或对设置特定项目
 *
 * @param bool|string $key 设置项
 * @param string $value 数值(NULL时不设置)
 * @param bool|string $defaultValue 取值时没有找到输出
 * @return mixed
 */
function C($key = FALSE, $value = NULL, $defaultValue = FALSE){
	$config = Context::$appConfig;
	if(!$key)	return $config;

	if($value != NULL){
		Context::$appConfig->$key = $value;
		return true;
	}

	if(isset($config->$key)){
		return $config->$key;
	}

	return $defaultValue;
}

/**
 * 根据数据获得值
 *
 * @param string $key 关键词
 * @param string $method 获得类型(G=GET P=POST GP=GET&POST)
 * @param string $defaultValue 默认值
 * @return string|NULL
 */
function GP($key, $method = 'GP', $defaultValue = NULL){
	if ($method != 'P' && isset($_GET[$key])) {
		return Char_cv($_GET[$key], false, true);
	} elseif ($method != 'G' && isset($_POST[$key])) {
		return Char_cv($_POST[$key], false, true);
	}

	return $defaultValue;
}

/**
 * 模板输出或调用
 *
 * @param mixed $tplName 模板名称 如果设置为绑定数组，则自动启发模板路径
 * @param mixed $bindArr 绑定数组，设置FALSE时只返回模板路径
 *
 * @return String|void
 */
function T($tplName, $bindArr = []){
    if (is_array($tplName)) {
        $actionPath = str_replace(['/app/action/', '.php'], '', Context::$appEntryPath);
        $bindArr = $tplName;
        $tplName = $actionPath;
    }

	if($bindArr === FALSE){
		return TemplateHelper::load($tplName);
	}else{
		if (is_string($bindArr)) {
			C("customLayout", $bindArr);
		}

		$arr = assign(NULL, NULL);
		if (is_array($bindArr))	$arr =  array_merge($arr, $bindArr);

		@extract($arr);
		require TemplateHelper::load($tplName);
	}
}

/**
 * 模板数据绑定
 * 
 * @param string $key 键名
 * @param string $value 值
 * @return void
 */
function assign($key, $value = NULL){
	return TemplateHelper::assign($key, $value);
}

/**
 * 载入APP目录的数据
 * 
 * @param string $path 路径
 * @param boolean $once 是否仅载入1次
 * @todo: .会替换成目录中的/
 * @throws Exception
 */
function import($path, $once = TRUE){
	static $loadedPath = array();

	$path = Context::$appBasePath.DIRECTORY_SEPARATOR.str_replace(".", DIRECTORY_SEPARATOR, $path).".php";
	if(!file_exists($path)){
		Logging::_logErr("Import not found: $path");
		throw new Exception("$path not load");
	}else{
		if(!in_array($path, $loadedPath) || !$once){
			require($path);
			$loadedPath[] = $path;
		}
	}
}

function get_date($format, $timestamp = NULL) {
	return date($format, $timestamp);
}

/**
 * 生成URL
 *
 * @param string $action 操作
 * @return mixed|string
 * @todo 如action=manager.login  => manager/login
 */
function url($action){
	$url = str_replace(".", "/", $action);
	$url .= Context::$appConfig->uriSuffix;

	return $url;
}


/***TemplateHelper functions**/
