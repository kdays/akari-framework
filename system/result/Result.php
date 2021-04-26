<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-08
 * Time: 15:35
 */

namespace Akari\system\result;

class Result {

    const TYPE_NONE = "NONE";
    const TYPE_HTML = 'HTML';
    const TYPE_JSON = 'JSON';
    const TYPE_VIEW = 'VIEW';
    const TYPE_CUSTOM = "CUSTOM";

    const CONTENT_HTML = 'text/html';
    const CONTENT_TEXT = 'text/plain';
    const CONTENT_PNG = 'image/png';
    const CONTENT_GIF = 'image/gif';
    const CONTENT_JPEG = 'image/jpeg';
    const CONTENT_JSON = 'application/json';
    const CONTENT_XML = 'application/xml';
    const CONTENT_INI = 'text/ini';
    const CONTENT_BINARY = 'application/octet-stream';
    const CONTENT_JAVACRIPT = 'application/javascript';

    public $type;
    public $data;
    public $meta;
    public $contentType;

    public function __construct($type, $data, $meta, $contentType = self::CONTENT_HTML, callable $callback = NULL) {
        $this->type = $type;
        $this->data = $data;
        $this->meta = $meta;

        $this->contentType = $contentType;

        if (is_callable($callback)) {
            call_user_func($callback, $this);
        }
    }

}
