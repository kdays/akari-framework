<?php
namespace Akari\system\result;

use Akari\system\http\HttpStatus;

Class JsonResult extends Result {

    var $data;
    public function doProcess() {
        HttpStatus::setContentType(HttpStatus::CONTENT_JSON);
        echo json_encode($this->data);
    }

    public function init($data) {
        $obj = new self();
        $obj->data = $data;

        return $obj;
    }

}