<?php

namespace Akari\system\docs;

#[\Attribute]
class DocsResponse {

    protected $name;
    protected $urlParams = [];
    protected $returnRef;

    public function __construct($name, $urlParams, $returnRef) {
        $this->name = $name;
        $this->urlParams = $urlParams;
        $this->returnRef = $returnRef;
    }

    public function toJson($data, $parentData = []) {
        if (!isset($data['response'])) {
            $data['response'] = [];
        }

        $data['response'][] = [
            'name' => $this->name,
            'urlParams' => $this->urlParams,
            'returnRef' => $this->returnRef,
        ];

        return $data;
    }

}