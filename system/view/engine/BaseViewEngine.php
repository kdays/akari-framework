<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/20
 * Time: 1:36
 */

namespace Akari\system\view\engine;


use Akari\system\ioc\Injectable;

abstract class BaseViewEngine extends Injectable {

    public $engineArgs = [];
    public $options = [];

    abstract public function parse($tplPath, array $data, $type, $onlyCompile = FALSE);
    abstract public function getResult($layoutResult, $screenResult);

    public function getCachePath(string $path) {
        //
    }

}
