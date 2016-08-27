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

    public function __construct($methodName, $className = NULL, $previous = NULL) {
        $methodName = is_array($methodName) ? implode("/", $methodName) : $methodName;
        $this->message = "not found $methodName on ". ($className == NULL ? " direct " : $className);
        $this->previous = $previous;
    }
    
}