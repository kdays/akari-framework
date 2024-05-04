<?php

namespace Akari\system\docs;

#[\Attribute]
class DocsGet
{

    protected $name;
    protected $description;
    protected $parent;

    public function __construct($name, $description, $parent = '') {
        $this->name = $name;
        $this->description = $description;
        $this->parent = $parent;
    }

    public function toJson($data, $parentData = []) {
        $data['name'] = $this->name;
        $data['description'] = $this->description;
        $data['request'] = 'Get';
        $data['parent'] = empty($this->parent) ? ($parentData['name'] ?? '') : $this->parent;

        return $data;
    }

}