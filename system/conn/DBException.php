<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/30
 * Time: 下午6:23
 */

namespace Akari\system\conn;

use Akari\system\exception\AkariException;

class DBException extends AkariException {

    private $queryString;

    public function getCodeErrorTrace() {
        $traces = $this->getTrace();
        foreach ($traces as $trace) {
            if (isset($trace['class']) && in_array($trace['class'], [
                    DBConnection::class,
                    SQLBuilder::class
                ])) {
                continue;
            }

            return $trace;
        }

        return NULL;
    }

    public function getCodeFile() {
        $trace = $this->getCodeErrorTrace();
        return $trace !== NULL ? $trace['file'] : '';
    }

    public function getCodeLine() {
        $trace = $this->getCodeErrorTrace();
        return $trace !== NULL ? $trace['line'] : '';
    }

    public function setQueryString(string $queryString) {
        $this->queryString = $queryString;
    }

    public function getQueryString() {
        return $this->queryString;
    }

}
