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
use Akari\system\result\Result;
use Akari\system\exception\AkariException;

class Trigger {

    protected static $beforeDispatchEvent = [];
    protected static $applicationStartEvent = [];
    protected static $applicationEndEvent = [];
    protected static $applicationOutputEvent = [];

    const TYPE_BEFORE_DISPATCH = "beforeDispatch";
    const TYPE_APPLICATION_START = "applicationStart";
    const TYPE_APPLICATION_END = "applicationEnd";
    const TYPE_APPLICATION_OUTPUT = "applicationOutput";

    public static function getPrefix() {
        return CLI_MODE ? 'Cli' : '';
    }

    public static function initEvent() {
        function _getTriggerList($type) {
            $prefix = Trigger::getPrefix();
            $trigger = Context::$appConfig->trigger;

            return empty($trigger[$prefix . $type]) ? [] : $trigger[$prefix . $type];
        }

        function _pushTrigger($trigger, array &$lists) {
            $triggerBaseNS = Context::$appBaseNS . NAMESPACE_SEPARATOR . "trigger" . NAMESPACE_SEPARATOR;
            $prefix = Trigger::getPrefix();

            try {
                if (class_exists($triggerBaseNS . $prefix . $trigger)) {
                    $lists[] = ['/.*/', $prefix . $trigger];
                }
            } catch (NotFoundClass $e) {

            }
        }

        self::$beforeDispatchEvent = _getTriggerList(self::TYPE_BEFORE_DISPATCH);

        $appStart = [];
        _pushTrigger("ApplicationStart", $appStart);
        $appStart = array_merge($appStart, _getTriggerList(self::TYPE_APPLICATION_START));
        _pushTrigger("AfterInit", $appStart);
        self::$applicationStartEvent = $appStart;

        unset($appStart);

        self::$applicationEndEvent = _getTriggerList(self::TYPE_APPLICATION_END);
        _pushTrigger("ApplicationEnd", self::$applicationEndEvent);

        self::$applicationOutputEvent = _getTriggerList(self::TYPE_APPLICATION_OUTPUT);
        _pushTrigger("ApplicationOutput", self::$applicationOutputEvent);
    }

    public static function handle($eventType, $requestResult = NULL) {
        $list = self::${$eventType . "Event"};
        $triggerBaseNS = Context::$appBaseNS . NAMESPACE_SEPARATOR . "trigger" . NAMESPACE_SEPARATOR;

        foreach ($list as $value) {
            if (count($value) < 2)  continue;
            list($re, $cls) = $value;

            if (!preg_match($re, Context::$uri)) {
                continue;
            }

            // clsName里如果已经有baseNS 那么就不用拼接了
            $clsName = in_string($cls, NAMESPACE_SEPARATOR) ? $cls : $triggerBaseNS . $cls;

            /** @var BaseTrigger $handler */
            try {
                $handler = new $clsName();
                $result = $handler->process($requestResult);
                if ($result !== NULL) {
                    if (!is_a($result, Result::class)) {
                        throw new WrongTriggerResultType(gettype($result), $clsName);
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



class WrongTriggerResultType extends AkariException  {

    public function __construct($returnType, $clsName) {
        $this->message = "Wrong Trigger Result: " . $returnType . " on " . $clsName;
    }

}


class MissingTrigger extends AkariException {

    public function __construct($clsName) {
        $this->message = "Trigger Not Found: " . $clsName;
    }

}
