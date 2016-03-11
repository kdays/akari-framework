<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/2
 * Time: 下午9:08
 */

namespace Akari\system\event;

use Akari\Context;
use Akari\NotFoundClass;

class Trigger {

    protected static $beforeDispatchEvent = [];
    protected static $applicationStartEvent = [];
    protected static $applicationEndEvent = [];
    
    const TYPE_BEFORE_DISPATCH = "beforeDispatch";
    const TYPE_APPLICATION_START = "applicationStart";
    const TYPE_APPLICATION_END = "applicationEnd";

    public static function initEvent() {
        $trigger = Context::$appConfig->trigger;
        $triggerBaseNS = Context::$appBaseNS. NAMESPACE_SEPARATOR. "trigger". NAMESPACE_SEPARATOR;

        $prefix = CLI_MODE ? 'CLI_' : '';
        
        $beforeDispatch = empty($trigger[$prefix. self::TYPE_BEFORE_DISPATCH]) ? [] : $trigger[$prefix. self::TYPE_BEFORE_DISPATCH];
        self::$beforeDispatchEvent = $beforeDispatch;

        $appStart = [];
        try {
            if (class_exists($triggerBaseNS. $prefix. "ApplicationStart")) {
                $appStart[] =  ['/.*/', $prefix. "ApplicationStart"];
            }
        } catch (NotFoundClass $e) {
            
        }
        
        if (!empty($trigger[$prefix. self::TYPE_APPLICATION_START])) {
            $appStart = array_merge($appStart, $trigger[$prefix. self::TYPE_APPLICATION_START]);
        }  
        
        try {
            if (class_exists($triggerBaseNS. $prefix. "AfterInit")) {
                $appStart[] =  ['/.*/',$prefix . "AfterInit"];
            }
        } catch (NotFoundClass $e) {
            
        }
        
        self::$applicationStartEvent = $appStart;

        $appEnd = empty($trigger[$prefix. self::TYPE_APPLICATION_END]) ? [] : $trigger[$prefix. self::TYPE_APPLICATION_END];
        try {
            if (class_exists($triggerBaseNS. $prefix. "ApplicationEnd")) {
                $appEnd[] = ['/.*/', $prefix. "ApplicationEnd"];
            }
        } catch (NotFoundClass $e) {
            
        }
        
        self::$applicationEndEvent = $appEnd;
    }

    public static function handle($eventType, $requestResult = NULL) {
        $list = self::${$eventType. "Event"};
        $triggerBaseNS = Context::$appBaseNS. NAMESPACE_SEPARATOR. "trigger". NAMESPACE_SEPARATOR;
        
        foreach ($list as $value) {
            list($re, $cls) = $value;

            if (!preg_match($re, Context::$uri)) {
                continue;
            }
            
            // clsName里如果已经有baseNS 那么就不用拼接了
            $clsName = in_string($cls, NAMESPACE_SEPARATOR) ? $cls : $triggerBaseNS . $cls;

            /** @var Rule $handler */
            try {
                $handler = new $clsName();
                $result = $handler->process($requestResult);
                if ($result !== NULL) {
                    if (!is_a($result, '\Akari\system\result\Result')) {
                        throw new WrongTriggerResultType(gettype($result),  $clsName);
                    }
                    $requestResult = $result;
                }
            } catch (StopEventBubbling $e) {
                break;
            } catch (NotFoundClass $e) {
                if ($e->className == $clsName) {
                    throw new MissingTrigger($clsName);
                }
                
                throw $e;
            }
        }

        return $requestResult;
    }

}



Class WrongTriggerResultType extends \Exception {

    public function __construct($returnType, $clsName) {
        $this->message = "Wrong Trigger Result Type: ". $returnType. " on ". $clsName;
    }

}




Class MissingTrigger extends \Exception {

    public function __construct($clsName) {
        $this->message = "Trigger Not Found: ". $clsName;
    }

}