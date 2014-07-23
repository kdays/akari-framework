<?php
namespace Akari\utility;

Class MessageHelper{
    /**
     * 跳转页面
     *
     * @param string $URL 跳转的URL
     * @param string $message 提示信息
     * @param int|number $time 跳转时间(0时直接跳转)
     * @return string
     */
	public static function jump($URL, $message = "页面正在跳转中，请稍候", $time = 5){
		if($time < 1){
			Header("Location: $URL");exit;
		}
		
		if(ob_get_level() > 1)  ob_end_clean();
		
		$version = AKARI_VERSION;
		$build = AKARI_BUILD;
		
		if(C("jumpTemplate")){
			return T(C("jumpTemplate"), Array(
				"URL" => $URL,
				"message" => $message,
				"version" => $version,
				"build" => $build,
				"time" => $time
			));
		}else{
			include(AKARI_PATH."/template/jump.htm");exit;
		}
	}

	public static function tip($message, $btn = FALSE) {
		if(ob_get_level() > 1) ob_end_clean();

		$version = AKARI_VERSION;
		$build = AKARI_BUILD;

		if(!$btn){
			$btn = array("返回" => "javascript:history.back();");
		}

		include(AKARI_PATH."template/showmessage.htm");exit;
	}
}