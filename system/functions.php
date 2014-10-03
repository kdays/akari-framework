<?php
!defined("AKARI_PATH") && exit;

use Akari\config\ConfigItem;
use Akari\Context;
use Akari\system\log\Logging;
use Akari\utility\I18n;
use Akari\utility\TemplateHelper;

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

/**
 * 检查目录是否越界，2个目录必须存在
 *
 * @param string $requestPath 检查的目录
 * @param bool $basePath 最低准限目录
 * @return bool
 * @todo PHP默认会缓存，无需再进行结果缓存
 */
function checkDir($requestPath, $basePath = false){
	if(!$basePath){
		$basePath = realpath(Context::$appBasePath);
	}
	
	$requestPath = realpath($requestPath);
	if(stripos($requestPath, $basePath) !== FALSE){
		return TRUE;
	}
	
	return FALSE;
}

/**
 * 获得缓存实例
 *
 * @param string $type 缓存类型
 * @param string $confId
 * @throws Exception
 * @return Akari\system\data\BaseCacheAdapter
 */
function getCacheInstance($type = "File", $confId = 'default'){
	static $instances = [];
	$type = $type ? $type : Context::$appConfig->defaultCacheType;
	$type = ucfirst($type);

	$cls = 'Akari\system\data'.NAMESPACE_SEPARATOR.$type."Adapter";
	if(!class_exists($cls)){
		throw new \Exception("[Akari.Cache] create cache instance Error, not found driver [ $type ]");
	}

	if(!isset($instances[$type."_".$confId])){
		$instances[$type."_".$confId] = new $cls($confId);
	}

	return $instances[$type."_".$confId];
}

/**
 * 缓存数据
 *
 * @param string $key 缓存键名
 * @param string $value 缓存数据
 * @param int $expired 过期时间
 * @param string $drvConfId 缓存配置设置子健
 * @param bool|string $drvName 缓存驱动名
 * @todo 如果value为NULL且expired为FALSE则为删除，只有value为NULL为取值
 */
function cache($key, $value = NULL, $expired = -1, $drvConfId = 'default', $drvName = false){
	$cls = getCacheInstance($drvName ? $drvName : false, $drvConfId);
	if($value === NULL && $expired === FALSE){
		return $cls->remove($key);
	}

	if(!is_numeric($expired))	$expired = strtotime($expired);

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
 * @return int
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

    // 检查目录是否存在类似文件 如果有就尝试调用__GET的magic method
    $confExt = [".php", ".yaml", ".yml"];
    $baseConfigDir = Context::$appBasePath."/app/config/";

    foreach ($confExt as $ext) {
        if (file_exists($baseConfigDir. Context::$mode. DIRECTORY_SEPARATOR. $key. $ext)) {
            return $config->$key;
        }

        if (file_exists($baseConfigDir. Context::$mode. ".". $key. $ext)) {
            return $config->$key;
        }

        if (file_exists($baseConfigDir. $key. $ext)) {
            return $config->$key;
        }
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
 *
 * @todo key为U.开头时，调用DataHelper，主要是为了方便URL重写的参数处理
 */
function GP($key, $method = 'GP', $defaultValue = NULL){
    $header = substr($key, 0, 2);
    $t = substr($key, 2);

    if ($header == 'U.') {
        if ($method != 'GP') {
            return \Akari\utility\DataHelper::set($t, $method);
        } else {
            return \Akari\utility\DataHelper::get(substr($key, 2), FALSE, $defaultValue);
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
		if (USE_RESULT_PROCESSOR) {
			$result = new \Akari\system\result\TemplateResult($tplName, $bindArr);
			return $result;
		}

		if (is_string($bindArr)) {
			C(ConfigItem::customLayout, $bindArr);
		}

		$arr = assign(NULL, NULL);
		if (is_array($bindArr))	$arr =  array_merge($arr, $bindArr);

		@extract($arr, EXTR_PREFIX_SAME, 'a_');
		require TemplateHelper::load($tplName);
	}
}

/**
 * 模板数据绑定
 * 
 * @param string $key 键名
 * @param string $value 值
 * @return array
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

	$name = explode(".", $path);
	$head = array_shift($name);
	
	if ($head == "core") {
		$path = AKARI_PATH. implode(DIRECTORY_SEPARATOR, $name). ".php";
	} else {
		$path = Context::$appBasePath. DIRECTORY_SEPARATOR. $head. DIRECTORY_SEPARATOR. implode(DIRECTORY_SEPARATOR, $name). ".php";
	}

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

/**
 * 对DateHelper的format一次封装
 *
 * @param string $format 格式
 * @param null|int $timestamp 时间戳，NULL取当前时间
 * @return bool|string
 */
function get_date($format, $timestamp = NULL) {
    return \Akari\utility\DateHelper::format($format, $timestamp);
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

/**
 * 格式化文件大小
 *
 * @param int $size 文件字节数
 * @param int $dec 单位
 * @return string
 */
function formatSize($size, $dec = 2){
    $a = array("B", "KB", "MB", "GB", "TB", "PB");
    $pos = 0;
    while ($size >= 1024) {
        $size /= 1024;
        $pos++;
    }
    return round($size, $dec)." ".$a[$pos];
}

/**
 * 抽取多维数组的某个元素,组成一个新数组,使这个数组变成一个扁平数组
 * 使用方法:
 * <code>
 * <?php
 * $fruit = array(array('apple' => 2, 'banana' => 3), array('apple' => 10, 'banana' => 12));
 * $banana = arrayFlatten($fruit, 'banana');
 * print_r($banana);
 * //outputs: array(0 => 3, 1 => 12);
 * ?>
 * </code>
 *
 * @param array $value 被处理的数组
 * @param string $key 需要抽取的键值
 * @return array
 */
function array_flatten(array $value, $key){
    $result = array();

    if ($value) {
        foreach ($value as $inval) {
            if (is_array($inval) && isset($inval[$key])) {
                $result[] = $inval[$key];
            } else {
                break;
            }
        }
    }

    return $result;
}

/**
 * 根据parse_url的结果重新组合url
 *
 * @access public
 * @param array $params 解析后的参数
 * @return string
 */
function build_url($params){
    return (isset($params['scheme']) ? $params['scheme'] . '://' : NULL)
    . (isset($params['user']) ? $params['user'] . (isset($params['pass']) ? ':' . $params['pass'] : NULL) . '@' : NULL)
    . (isset($params['host']) ? $params['host'] : NULL)
    . (isset($params['port']) ? ':' . $params['port'] : NULL)
    . (isset($params['path']) ? $params['path'] : NULL)
    . (isset($params['query']) ? '?' . $params['query'] : NULL)
    . (isset($params['fragment']) ? '#' . $params['fragment'] : NULL);
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

/**
 * 调试个体成熟
 *
 * @param mixed $var 参数
 */
function dump($var) {
	static $dispCSS = FALSE;

	if (!CLI_MODE && !$dispCSS) {
		$preStyle = <<<'PRE_STYLE'
<style>
pre{
	display: block;
	overflow: auto;
	background: #fafafa;
	color: #333;
	max-height: 300px;
	border: 1px #eee solid;
	max-width: 90%;
	margin: 10px;
	padding: 5px 10px;
}
</style>
PRE_STYLE;

		$dispCSS = true;
		echo $preStyle;
	}
	\Akari\system\exception\ExceptionProcessor::addDump($var);

    if (is_array($var)) {
	    $dump = function() use($var) {
		    echo "<pre>"; print_r($var); echo "</pre>";
	    };

	    CLI_MODE ? print_r($var) : $dump();
    } elseif (is_string($var)) {
	    echo (CLI_MODE ? $var : "<pre>".$var."</pre>")."\n";
    } else {
        var_dump($var);
    }
}

/**
 * @param $val
 * @return mixed
 */
function removeXSS($val){
    // remove all non-printable characters. CR(0a) and LF(0b) and TAB(9) are allowed
    // this prevents some character re-spacing such as <java\0script>
    // note that you have to handle splits with \n, \r, and \t later since they *are* allowed in some inputs
    $val = preg_replace('/([\x00-\x08]|[\x0b-\x0c]|[\x0e-\x19])/', '', $val);

    // straight replacements, the user should never need these since they're normal characters
    // this prevents like <IMG SRC=&#X40&#X61&#X76&#X61&#X73&#X63&#X72&#X69&#X70&#X74&#X3A&#X61&#X6C&#X65&#X72&#X74&#X28&#X27&#X58&#X53&#X53&#X27&#X29>
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';

    for ($i = 0; $i < strlen($search); $i++) {
        // ;? matches the ;, which is optional
        // 0{0,7} matches any padded zeros, which are optional and go up to 8 chars

        // &#x0040 @ search for the hex values
        $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val); // with a ;
        // &#00064 @ 0{0,7} matches '0' zero to seven times
        $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val); // with a ;
    }

    // now the only remaining whitespace attacks are \t, \n, and \r
    $ra1 = Array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
    $ra2 = Array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
    $ra = array_merge($ra1, $ra2);

    $found = true; // keep replacing as long as the previous round replaced something
    while ($found == true) {
        $val_before = $val;
        for ($i = 0; $i < sizeof($ra); $i++) {
            $pattern = '/';
            for ($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                    $pattern .= '|';
                    $pattern .= '|(&#0{0,8}([9|10|13]);)';
                    $pattern .= ')*';
                }
                $pattern .= $ra[$i][$j];
            }
            $pattern .= '/i';
            $replacement = substr($ra[$i], 0, 2).'<x>'.substr($ra[$i], 2); // add in <> to nerf the tag
            $val = preg_replace($pattern, $replacement, $val); // filter out the hex tags

            if ($val_before == $val) {
                // no replacements were made, so exit the loop
                $found = false;
            }
        }
    }

    return $val;
}