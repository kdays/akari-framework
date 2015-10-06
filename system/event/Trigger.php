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

    public static function initEvent() {
        $trigger = Context::$appConfig->trigger;
        $triggerBaseNS = Context::$appBaseNS. NAMESPACE_SEPARATOR. "trigger". NAMESPACE_SEPARATOR;

        $beforeDispatch = empty($trigger['beforeDispatch']) ? [] : $trigger['beforeDispatch'];
        self::$beforeDispatchEvent = $beforeDispatch;

        $appStart = [];
        if (class_exists($triggerBaseNS. "ApplicationStart")) {
            $appStart[] =  ['/.*/', "ApplicationStart"];
        }
        
        if (is_array($trigger['applicationStart'])) {
            $appStart = array_merge($appStart, $trigger['applicationStart']);
        }  
        
        if (class_exists($triggerBaseNS. "AfterInit")) {
            $appStart[] =  ['/.*/', "AfterInit"];
        }
        
        self::$applicationStartEvent = $appStart;


        $appEnd = empty($trigger['applicationEnd']) ? [] : $trigger['applicationEnd'];
        if (class_exists($triggerBaseNS. "ApplicationEnd")) {
            $appEnd[] = ['/.*/', "ApplicationEnd"];
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
            
            $clsName = $triggerBaseNS. $cls;

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
                throw new MissingTrigger($clsName);
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