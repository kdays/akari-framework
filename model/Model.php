<?php
namespace Akari\model;

!defined("AKARI_PATH") && exit;

Class Model{
    /**
     * 日志记录函数
     * @param string $msg 消息
     */
	public function __log($msg){
		Logging::_log($msg);
	}
    
	/**
	 * 错误记录函数
	 * @param string $msg 信息
	 */
	public function __logErr($msg){
		Logging::_logErr($msg);
	}
}