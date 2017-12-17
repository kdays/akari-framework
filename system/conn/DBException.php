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

    public function __construct($message = "", $code = 0, $previous = NULL) {
        parent::__construct($message, $code, $previous);
        foreach ($this->getTrace() as $trace) {
            if (isset($trace['class']) && in_array($trace['class'], [
                    DBConnection::class,
                    SQLBuilder::class
                ])) {
                continue;
            }

            $this->file = $trace['file'];
            $this->line = $trace['line'];

            break;
        }
    }

    public function setQueryString(string $queryString) {
        $this->queryString = $queryString;
    }

    public function getQueryString() {
        return $this->queryString;
    }

}
