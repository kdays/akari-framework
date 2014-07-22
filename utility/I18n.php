<?php
namespace Akari\utility;

use Akari\Context;

!defined("AKARI_PATH") && exit;

Class I18n{
	public static $data = array();
	public static $loaded = array();
    
	/**
	 * 查找，获得语言路径
	 * 
	 * @param string $name 语言包名
	 * @return string|boolean
	 */
	public static function getpath($name){
		$defLang = C("language");
		$langPath = false;

		$baseLangDir = Context::$appBasePath."/app/language";
		// 如果用户有定义的话 usrLanguage

		$usrLang = false;
		if(defined("USRLANG")){
			$usrLang = USRLANG;
		}elseif(C("usrLanguage")){
			$usrLang = C("usrLanguage");
		}

		// 处理用户语言
		if($usrLang){
			if(file_exists("$baseLangDir/$usrLang/$name.php")){
				return "$baseLangDir/$usrLang/$name.php";
			}elseif(file_exists("$baseLangDir/$name.$usrLang.php")){
				return "$baseLangDir/$name.$usrLang.php";
			}
		}

		if($usrLang != $defLang){
			if(file_exists("$baseLangDir/$defLang/$name.php")){
				return "$baseLangDir/$defLang/$name.php";
			}elseif(file_exists("$baseLangDir/$name.$defLang.php")){
				return "$baseLangDir/$name.$defLang.php";
			}
		}

		if(file_exists("$baseLangDir/$name.php")){
			return "$baseLangDir/$name.php";
		}	
		return FALSE;
	}
    
	/**
	 * 载入语言包
	 * 
	 * @param string $name 包名
	 * @param string $prefix 前缀
	 * @throws Exception
	 * @return boolean
	 * @todo 设置中有usrLanguage时(使用C设定)，会以usrLanguage优先目标，再是设置的language选项
	 * 语言包放在app\language目录下，此外按照language\(languageNameDir)\(LanguagePackName).php，
	 * 然后是language\(LanguagePackName).(LanguageName).php
	 * 最后没有找到再是language\(Language).php
	 */
	public static function load($name, $prefix = ""){
		if(isset(self::$loaded[$prefix.$name]))	return false;
		$langPath = self::getpath($name);

		if(!$langPath){
			throw new \Exception("[Akari.I18n] not found [ $prefix $name ]");
		}

		self::$loaded[$prefix.$name] = time();
		$now = self::getlang($langPath);
		foreach($now as $key => $value){
			self::$data[$prefix.$key] = $value;
		}

		return true;
	}
    
	/**
	 * 根据语言包获得语言
	 * 
	 * @param string $id 语言串
	 * @param array $L 替换参数
	 * @param string $prefix 前缀
	 * @return Ambigous <mixed, string, multitype:>
	 * @todo 可用函数L代替，此外$L实际替换就是%key%=>$L[%key%]类似。
	 * prefix是在load时设定
	 */
	public static function get($id, $L = Array(), $prefix = ""){
		$id = $prefix.$id;
		$lang = isset(self::$data[$id]) ? self::$data[$id] : "[$id]";

		foreach($L as $key => $value){
			$lang = str_replace("%$key%", $value, $lang);
		}

		// 处理![语言句子] 或被替换成L(语言句子)
		$lang = preg_replace_callback('/\!\[(\S+)\]/i', function($matches){
			if (isset($matches[1])) {
				return L($matches[1]);
			}

			return $matches[0];
		}, $lang);

		return $lang;
	}

	public static function getlang($path){
		return require($path);
	}
}