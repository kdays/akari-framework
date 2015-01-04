<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:09
 */

namespace Akari\system\event;

use Akari\Context;
use Akari\exception\ExceptionProcessor;
use Akari\utility\helper\Logging;

Class Trigger {

    const KEY_URI = 0;
    const KEY_CLASS = 1;

    use Logging;

    protected static $h;
    private $preRules = [];
    private $afterRules = [];

    public static function getInstance() {
        if(!isset(self::$h)){
            self::$h = new self();
            self::$h->initRules();
        }

        return self::$h;
    }

    public function initRules() {
        $config = Context::$appConfig->uriEvent;
        if (!empty($config['pre'])) $this->preRules = $config['pre'];
        if (!empty($config['after']))   $this->afterRules = $config['after'];

        $baseDir = implode(DIRECTORY_SEPARATOR, Array(
            Context::$appEntryPath, 'trigger', ''
        ));

        if (file_exists($baseDir. "AfterInit.php")) {
            $this->preRules[] = Array("/.*/", "AfterInit");
        }

        if (file_exists($baseDir. "ApplicationEnd.php")) {
            $this->afterRules[] = Array("/.*/", "ApplicationEnd");
        }

        if (file_exists($baseDir. "ApplicationStart.php")) {
            array_unshift($this->preRules, ['/.*/', 'ApplicationStart']);
        }
    }

    private function dispatch($type, $requestResult = NULL) {
        $uri = Context::$uri;

        $commitName = $type."Rules";

        foreach ($this->$commitName as $rule) {
            $re = $rule[ self::KEY_URI ];
            $clsName = $rule[ self::KEY_CLASS ];

            if (preg_match($re, $uri)) {
                $cls = Context::$appBaseNS. NAMESPACE_SEPARATOR ."trigger". NAMESPACE_SEPARATOR. $clsName;
                if (!class_exists($cls)) {
                    throw new MissingTrigger($clsName);
                }

                /**
                 * @var $nowCls \Akari\system\event\Rule
                 */
                $nowCls = new $cls();
                try {
                    $result = $nowCls->process($requestResult);
                    if ($result !== NULL) {
                        if (!is_a($result, '\Akari\system\result\Result')) {
                            throw new WrongTriggerResultType(gettype($result),  $clsName);
                        }
                        $requestResult = $result;
                    }
                } catch (BreakTriggerEvent $e) {
                    break;
                }

                return $requestResult;
            }
        }
    }

    /**
     * Pre预处理事件
     */
    public function commitPreRule(){
        return $this->dispatch('pre');
    }

    /**
     * After之后的事件
     *
     * @param $result
     * @return \Akari\system\result\Result
     */
    public function commitAfterRule($result){
        return $this->dispatch('after', $result);
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

Class BreakTriggerEvent extends \Exception {


}