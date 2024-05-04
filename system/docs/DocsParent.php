<?php

namespace Akari\system\docs;

#[\Attribute]
class DocsParent {

    protected $name;

    public function __construct($name) {
        $this->name = $name;
    }

    public function toJson($data) {
        $data['name'] = $this->name;

        return $data;
    }

}