<?php
Class Event{
    public static $queue = Array();
    
    /**
     * 增加一个事件监听
     * 
     * @param string $eventType 事件名
     * @param callable $callback 事件回调 
     * @param int $sort 排序数字 
     * @todo 排序是因为事件执行中如果return FALSE会导致事件不继续，所以需要排序<br />
     * 事件名的格式必须是A.B，B使用*时，fire调用A.x的任意事件都会触发<br />
     * 举例Auth.Login被执行时，监听器会检查2个，分别是Auth.* 和Auth.Login
     */
    public static function listen($eventType, $callback, $sort = 0) {
        if(!is_callable($callback)){
            return FALSE;
        }
        
        list($gNamespace, $sNamespace) = explode(".", $eventType);
        
        if(isset(self::$queue[$gNamespace][$sNamespace])){
            foreach(self::$queue[$gNamespace][$sNamespace] as $value){
                if($value[0] == $callback) {
                    return FALSE;
                }
            }
        }
        
        self::$queue[$gNamespace][$sNamespace][] = array($callback, $sort);
    }
    
    /**
     * 执行事件
     * 
     * @param string $eventType 事件名 （A.B  B不可为*）
     * @param array $params 传递时的参数 
     */
    public static function fire($eventType, $params = array()){
        list($gNamespace, $sNamespace) = explode(".", $eventType);
        
        $eventQueue = array();
        $nowQueue = self::$queue;
        
        if(isset($nowQueue[$gNamespace])){
            $nowQueue = $nowQueue[$gNamespace];
            
            if(isset($nowQueue["*"])) {
                $eventQueue = array_merge($eventQueue, $nowQueue["*"]);
            }
            
            if(isset($nowQueue[$sNamespace])) {
                $eventQueue = array_merge($eventQueue, $nowQueue[$sNamespace]);
            }
        }
        
        if(!empty($eventQueue)){
            usort($eventQueue, '_doEventSort');
            
            foreach($eventQueue as $value){
                if($value[0]($params) === FALSE){
                    break;
                }
            }
        }
    }
}

function _doEventSort($a, $b){
    return $a[1] - $b[1];
}