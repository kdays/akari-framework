<?php

namespace Akari\system\docs;

#[\Attribute]
class DocsProperty
{

    protected $name;
    protected $type;
    protected $required;

    public function __construct($name, $type, $required = true) {
        $this->name = $name;
        $this->type = $type;
        $this->required = $required;
    }

    public function toJson($data) {
        $data['name'] = $this->name;
        $data['type'] = $this->type;
        $data['required'] = $this->required;

        return $data;
    }

}