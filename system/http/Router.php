<?php
!defined("AKARI_PATH") && exit;

Class Router{
	private $config;
	private $request;
	private static $r;

	public static function getInstance() {
        if (self::$r == null) {
            self::$r = new self();
        }
        return self::$r;
    }
    
    private function __construct(){
    	$this->request = Request::getInstance();
    	$this->config = Context::$appConfig;
    }

    private function clearURI($uri){
    	$queryString = $_SERVER['QUERY_STRING'];
    	if(strlen($queryString) > 0){
    		$uri = substr($uri, 0, -strlen($queryString)  -1);
    	}
    	
    	$scriptName = Context::$appEntryName;
        $scriptNameLen = strlen($scriptName);
        if (substr($uri, 1, $scriptNameLen) === $scriptName) {
            $uri = substr($uri, $scriptNameLen + 1);
        }
    	
    	return $uri;
    }
	
	public function resolveURI(){
		$uri = null;
		
		$config = $this->config;
		switch($config->uriMode){
			case AKARI_URI_AUTO:
				$uri = $this->request->getPathInfo();
			
				if(empty($uri)){
					if(isset($_GET['uri']))	$uri = $_GET['uri'];
					if(empty($uri)){
						$uri = $this->clearURI($this->request->getRequestURI());
					}
				}
				break;

			case AKARI_URI_PATHINFO:
				$uri = $this->request->getPathInfo(); break;

			case AKARI_URI_QUERYSTRING:
				if(isset($_GET['uri']))	$uri = $_GET['uri']; break;

			case AKARI_URI_REQUESTURI:
				$uri = $this->clearURI($this->request->getRequestURI());break;
		}
		
		$uri = preg_replace('/\/+/', '/', $uri); //把多余的//替换掉..
		
		if(!$uri || $uri == '/' || $uri == '/'.Context::$appEntryName){
			$uri = $config->uriDefault;
		}
		
		$uriParts = explode('/', $uri);
		if(count($uriParts) < 3){
			$uri = dirname($config->uriDefault).'/'.array_pop($uriParts);
		}
		
		if (substr($uri, -1) === '/') {
			$uri .= 'index';
		}else{
			if (!empty($config->uriSuffix)) {
                $suffix = substr($uri, -strlen($config->uriSuffix));
                if ($suffix === $config->uriSuffix) {
                    $uri = substr($uri, 0, -strlen($config->uriSuffix));
                } else {
                    throw new Exception('Invaild URI');
                }
            }
		}
		
		if($uri[0] == '/'){
			$uri = substr($uri, 1);
		}
		
		return $uri;
	}
}