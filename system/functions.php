<?php
!defined("AKARI_PATH") && exit;

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

function createdir($path, $index = false){
	if(is_dir($path))	return ;
	createfolder(dirname($path), $index);
	
	@mkdir($path);
	@chmod($path,0777);
	if(!$index){
		@fclose(@fopen($path.'/index.html','w'));
		@chmod($path.'/index.html',0777);
	}
}

function deletedir($path){
	if(rmdir($path) == false && is_dir($path)){
		if($dp = opendir($path)){
			while(($fp = readdir($dp)) !== false){
				if($fp != "." && $fp != ".."){
					if(is_dir("$path/$fp")){
						deletefolder("$path/$fp");
					}else{
						unlink("$path/$fp");
					}
				}
			}
			
			closedir($dp);
			rmdir($path);
		}else{
			return false;
		}
	}
}

function movefile($dstfile, $srcfile){
	createfolder(dirname($dstfile));
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

function getCacheInstance($type = "File"){
	static $CacheInstance = Array();
	$type = $type ? ucfirst($type) : Context::$appConfig->defaultCacheType;

	$cls = $type."Adapter";
	if(!class_exists($cls)){
		throw new Excepton("[Akari.Cache] Get CacheInstance Error, Not Found [$type]");
	}

	if(!$CacheInstance[$type]){
		$CacheInstance[$type] = new $cls();
	}

	return $CacheInstance[$type];
}

function cache($key, $value = NULL, $expried = -1, $opts = array()){
	$cls = getCacheInstance($opts["type"]);

	if($value == NULL && $expried === FALSE){
		return $cls->remove($key);
	}

	if(!is_numeric($expried))	$expried = strtotime($expried);

	if($value == NULL){
		return $cls->get($key);
	}
	return $cls->set($key, $value, $expried);
}

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
		$mixed = str_replace(array('"',"'","\t",'  '),array('&quot;','&#39;','    ','&nbsp;&nbsp;'),$mixed);
	}
	return $mixed;
}

/**
 * 根据Model返回单例
 *
 */
function M($ModelName){
	static $tmpModel = array();

	$modelPath = Context::$appBasePath."/app/model/$ModelName.php";
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

function L($key, $L = Array(), $prefix = ''){
	return I18n::get($key, $L, $prefix);
}

function C($key = FALSE, $value = NULL, $defaultValue = FALSE){
	$config = Context::$appConfig;
	if(!$key)	return $config;

	if($value != NULL){
		$config->$$key = $value;
		return true;
	}

	if(isset($config->$$key)){
		return $config->$$key;
	}

	return $defaultValue;
}

/**
 * 根据数据获得值
 *
 **/
function GP($key, $method = 'GP'){
	if ($method != 'P' && isset($_GET[$key])) {
		return Char_cv($_GET[$key], false, true);
	} elseif ($method != 'G' && isset($_POST[$key])) {
		return Char_cv($_POST[$key], false, true);
	}

	return NULL;
}

/**
 *
 *
 * @todo: if bindArr == false only return path
 **/
function T($tplName, $bindArr = false){
	if($bindArr === FALSE){
		return TemplateHelper::load($tplName);
	}else{
		$arr = is_array($bindArr) ? array_merge($bindArr, V("", false)) : V("", false);
		@extract($arr);
		require TemplateHelper::load($tplName);
	}
}

function V($key, $value){
	static $data = array();
	if($key != "") $data[$key] = $value;

	return $data;
}

function import($path){
	$path = Context::$appBasePath.DIRECTORY_SEPARATOR.str_replace(".", DIRECTORY_SEPARATOR, $path).".php";
	if(!file_exists($path)){
		Logging::_logErr("Import not found: $path");
		throw new Exception("$path not load");
	}else{
		require($path);
	}
}