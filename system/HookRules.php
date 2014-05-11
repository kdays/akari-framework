<?php
Class HookRules{
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

	public function initRules(){
		$config = Context::$appConfig->hookRules;
		if(!empty($config['pre']))	$this->preRules = $config['pre'];
		if(!empty($config['after']))	$this->afterRules = $config['after'];

		$this->preRules[] = Array("/.*/", "AfterInit");
		$this->afterRules[] = Array("/.*/", "ApplicationEnd");
	}

	private function commitRules($type){
		$uri = Context::$uri;
        $arrayName = $type . 'Rules';

        foreach ($this->$arrayName as $rule) {
            $re = $rule[0];
            $hookPath = Context::$appBasePath."/app/hook/$rule[1].php";

            if (preg_match($re, $uri) === 1) {
            	if(file_exists($hookPath)){
            		require $hookPath;
            	}else{
            		throw new Exception("[akari.Hook] not found hook $rule[1]");
            	}
            }
        }
	}

	public function commitPreRule(){
		$this->commitRules('pre');
	}

	public function commitAfterRule(){
		$this->commitRules('after');
	}
}