<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/27
 * Time: 下午2:17
 */

namespace Akari\system\router;

use Akari\system\exception\AkariException;

class NotFoundURI extends AkariException {

    private $onMethod;
    private $onClass;

    public function __construct($methodName, $className = NULL, $previous = NULL) {
        $methodName = is_array($methodName) ? implode("/", $methodName) : $methodName;

        $this->onMethod = $methodName;
        $this->onClass = $className;

        $this->message = "not found " . $methodName;
        if ($className) {
            $this->message .= " on " . $className;
        } 

        $this->previous = $previous;
    }

    public function getOnMethod() {
        return $this->onMethod;
    }

    public function getOnClass() {
        return $this->onClass;
    }
}
