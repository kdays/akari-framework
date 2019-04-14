<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-08
 * Time: 13:14
 */

namespace Akari\exception;


class LoaderClassNotExists extends \Exception {

    protected $class;

    public function __construct(string $class) {
        $this->class = $class;
        $this->message = "Class Loader Error: " . $class . " not exists";
    }

    public function getClass() {
        return $this->class;
    }

}
