<?php

namespace Akari\system\docs;

enum DocsParamType implements \JsonSerializable
{

    case INT;
    case STRING;

    public function jsonSerialize(): string {
        return match($this) {
            self::INT => 'integer',
            self::STRING => 'string',
        };
    }

}