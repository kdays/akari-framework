<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-08
 * Time: 13:13
 */

namespace Akari\exception;

use Akari\system\db\DBModel;

class DBException extends \Exception {

    private $queryString;

    public function __construct($message = "", $code = 0, $previous = NULL) {
        parent::__construct($message, $code, $previous);

        $trace = $this->getTrace();
        if (!empty($trace)) {
            array_shift($trace);
            foreach ($trace as $line) {
                if (isset($line['class']) && in_array($line['class'], [
                    DBModel::class
                    ])) {
                    array_shift($trace);
                }
            }

            $oneTrace = $trace[0];

            $this->file = $oneTrace['file'];
            $this->line = $oneTrace['line'];
        }
    }

    public function setQueryString(string $queryString) {
        $this->queryString = $queryString;
    }

    public function getQueryString() {
        return $this->queryString;
    }

}
