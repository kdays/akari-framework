<?php
!defined("AKARI_PATH") && exit;

Class TemplateHelper{
	public static function load($tplName){
		if($bDir = C("templateBaseDir")){
			$tplName = "$bDir/$tplName";
		}

		$tplPath = Context::$appBasePath."/app/template/$tplName";
		$tplPath .= Context::$appConfig->templateSuffix ? Context::$appConfig->templateSuffix : ".htm";

		if(!file_exists($tplPath)){
			throw new Exception("[Akari.Utility.TemplateHelper] not found [ $tplName ]");
		}

		$tplId = str_replace('/', '_', $tplName);
		$cachePath = Context::$appBasePath.Context::$appConfig->templateCache."/$tplId.php";
		if(file_exists($cachePath) && filemtime($tplPath) < filemtime($cachePath)){
			return $cachePath;
		}else{
			$content = "<?php !defined('AKARI_VERSION') && exit(); ?>";
			$content .= self::parse(readover($tplPath));

			writeover($cachePath, $content);
			return $cachePath;
		}
	}

	public static function parse($template){
		$const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
		$var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";
		
		$template = preg_replace('/<!--#(.*?)-->/ieu', "self::command_parse('\\1')", $template);
		$template = preg_replace('/\{\%(.*?)\}/ieu', "self::command_lang('\\1')", $template);
		
		$template = preg_replace("/\{$const_regexp\}/s", "<?=\\1?>", $template);
		$template = preg_replace("/$var_regexp/es", "self::addquote('<?=\\1?>')", $template);
		$template = preg_replace("/\<\?\=\<\?\=$var_regexp\?\>\?\>/es", "self::addquote('<?=\\1?>')", $template);
		
		$template = str_replace("_#_", "\$", $template);
		
		return $template;
	}

	
	public static function command_parse($str){
		$str = str_replace("$", "_#_", $str);
		$str = explode(" ", $str);
		$command = array_shift($str);
		$end_str = implode(" ", $str);
		
		switch($command){
			case "if":
				return "<?php if($end_str){?>";
			case "else":
				return "<?php }else{ ?>";
			case "elseif":
				return "<?php }elseif($end_str){ ?>";
			case "template":
				return "<?php require TH_template('$end_str'); ?>";
			case "module":
				if(isset($str[1])){
					return "<?php TH_module('$str[0]', \"$str[1]\"); ?>";
				}else{
					return "<?php TH_module('$end_str'); ?>";
				}
			case "eval":
				return "<?php eval('$end_str'); ?>";
			case "var":
				return "<?php echo($end_str); ?>";
			case "for":
				return "<?php for($end_str){ ?>";
			case "loop":
				return "<?php if(is_array($str[0])||is_object($str[0]))foreach({$end_str}){ ?>";
			case "loopend":
			case "endloop":
			case "/loop":
			case "/if":
			case "/for":
			case "endfor":
			case "forend":
			case "endif":
			case "ifend":
				return "<?php } ?>";
			default:
				return "<!--[Invild Command: $command]-->";
		}
	}
	
	public static function addquote($var) {
		return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $var));
	}
	
	public static function command_lang($str){
		$command = explode(" ", $str);
		$langid = array_shift($command);
		if(empty($command)){
			return "<?php TH_lang(\"$langid\"); ?>";
		}else{
			$commands = implode("&", $command);
			return "<?php TH_lang(\"$langid\", '$commands'); ?>";
		}
	}
}

function TH_lang($id, $command = ''){
	$command = explode("&", $command);
	foreach($command as $value){
		$tmp = explode("=", $value);
		$L[$tmp[0]] = $tmp[1];
	}
	echo I18n::get($id, $L);
}

function TH_template($id){
	return TemplateHelper::load($id);
}

function TH_module($id, $data = ''){
	$appPath = Context::$appBasePath."/app/lib/module_{$id}.php";
	$corePath = Context::$appBasePath."/core/system/module/$id.php";
	$clsName = $id."Module";

	if(file_exists($appPath)){
		require_once($appPath);
	}elseif(file_exists($corePath)){
		require_once($corePath);
	}else{
		throw new Exception("Module $id not found");
	}

	$clsObj = $clsName::getInstance();

	return $clsObj->run($data);
}