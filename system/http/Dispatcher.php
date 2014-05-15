<?php
!defined("AKARI_PATH") && exit;

Class Dispatcher{
	private $config;
	private static $r;

	public static function getInstance() {
        if (self::$r == null) {
            self::$r = new self();
        }
        return self::$r;
    }
    
    private function __construct(){
    	$this->config = Context::$appConfig;
    }

    public function invokeTask($URI = ''){
        $list = explode("/", $URI);
        $taskName = array_shift($list);

        $path = Context::$appBasePath."/app/task/$taskName.php";
        if(!file_exists($path)){
            Logging::_logErr("Task [ $taskName ] Not Found");
            return false;
        }

        return $path;
    }

    public function invoke($URI = ''){
        $list = explode("/", $URI);
        $basePath = Context::$appBasePath."/app/action/";
        $URLRewrite = Context::$appConfig->URLRewrite;

        foreach($URLRewrite as $key => $value){
            if(preg_match($key, $URI)){
                if(stripos($value, 'app/') === false){
                    $value = "/app/action/$value";
                }
                return Context::$appBasePath.$value.".php";
            }
        }

        // 原则上不可能那么多URI 只是为了备用 一般4层就很多了
        $count = count($list);

        if($count > 10){
            throw new Exception("Invild URI");
        }

        // 如果有子目录的操作会处理发送最近一个
        if($count > 1){
            for($i = 0; $i < $count - 1; $i++){
                $filename = array_pop($list);
                $name = implode(DIRECTORY_SEPARATOR, $list);

                $path = $basePath.$name.DIRECTORY_SEPARATOR.$filename.".php";
                if(file_exists($path)){
                    return $path;
                }
            }
        }

        //首先检查是否有类似名称的
        if(file_exists($path = $basePath.array_shift($list).".php")){
            return $path;
        }

        if($count == 1 && file_exists($path = $basePath."default.php")){
             return $path;
        }

        return FALSE;
    }
}