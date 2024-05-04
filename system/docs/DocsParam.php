<?php

namespace Akari\system\docs;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_FUNCTION | Attribute::IS_REPEATABLE)]
class DocsParam {

    protected $name;
    protected $type;
    protected $description;
    protected $required;
    protected $defaultValue;

    public function __construct($name, $type, $description, $required = false, $defaultValue = NULL) {
        $this->name = $name;
        $this->type = $type;
        $this->description = $description;
        $this->required = $required;
        $this->defaultValue = $defaultValue;
    }

    public function toJson($data, $parentData = []) {
        if (!isset($data['params'])) {
            $data['params'] = [];
        }

        $data['params'][] = [
            'name' => $this->name,
            'type' => $this->type,
            'description' => $this->description,
            'required'  => $this->required,
            'defaultValue' => $this->defaultValue
        ];

        return $data;
    }

}