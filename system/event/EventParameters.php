<?php


namespace Akari\system\event;


class EventParameters {

    protected $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function get() {
        return $this->data;
    }

    public function update($data) {
        $this->data = $data;
    }

}
