<?php
namespace Akari\system;

use Akari\Context;

!defined("AKARI_PATH") && exit;

Class TriggerRule{
	protected static $h;

	private $preRules = array();
	private $afterRules = array();

	public static function getInstance(){
		if(!isset(self::$h)){
			self::$h = new self();
			self::$h->initRules();
		}

		return self::$h;
	}
	
	/**
	 * 初始化规则
	 */
	public function initRules(){
		$config = Context::$appConfig->triggerRule;
		if(!empty($config['pre']))	$this->preRules = $config['pre'];
		if(!empty($config['after']))	$this->afterRules = $config['after'];

		// 检查目录是否有AfterInit、ApplicationEnd、ApplicationStart
		if (file_exists(Context::$appBasePath."/app/trigger/AfterInit.php")) {
			$this->preRules[] = Array("/.*/", "AfterInit");
		}
		
		if (file_exists(Context::$appBasePath."/app/trigger/ApplicationEnd.php")) {
			$this->afterRules[] = Array("/.*/", "ApplicationEnd");
		}
		
		if (file_exists(Context::$appBasePath."/app/trigger/ApplicationStart.php")) {
			array_unshift($this->preRules, ['/.*/', 'ApplicationStart']);
		}
	}
	
	/**
	 * 触发器规则提交
	 * 
	 * @param string $type 事件名
	 * @throws Exception
	 */
	private function commitRules($type){
		$uri = Context::$uri;
		$arrayName = $type . 'Rules';

		foreach ($this->$arrayName as $rule) {
			$re = $rule[0];
			$hookPath = Context::$appBasePath."/app/trigger/$rule[1].php";

			if (preg_match($re, $uri) === 1) {
				if(file_exists($hookPath)){
					require $hookPath;
				}else{
					throw new \Exception("[akari.trigger] not found trigger $rule[1]");
				}
			}
		}
	}
	
	/**
	 * Pre预处理事件
	 */
	public function commitPreRule(){
		$this->commitRules('pre');
	}
	
	/**
	 * After之后的事件
	 */
	public function commitAfterRule(){
		$this->commitRules('after');
	}
}