<?php
namespace Akari\system\result;

Class DefaultResult extends Result {

    var $data;
    public function doProcess() {
        echo $this->data;
    }

    public function init($data) {
        $obj = new self();
        $obj->data = $data;

        return $obj;
    }

}