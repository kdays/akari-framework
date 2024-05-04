<?php

namespace Akari\system\docs;

#[\Attribute]
class DocsSchema
{

    protected $name;
    protected $key;
    public function __construct($name, $key = NULL) {
        $this->name = $name;
        $this->key = $key;
    }

    public function toJson($data) {
        $data['name'] = $this->name;
        $data['key'] = $this->key;

        return $data;
    }

}