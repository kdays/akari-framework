<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:02
 */

namespace Akari\system\result;

Class Result {

    const TYPE_NONE = "NONE";
    const TYPE_HTML = 'HTML';
    const TYPE_JSON = 'JSON';
    const TYPE_XML = 'XML';
    const TYPE_TPL = 'TPL';
    const TYPE_DOWN = 'DOWNLOAD';
    const TYPE_TEXT = "TEXT";

    const TYPE_JPEG = 'JPEG';
    const TYPE_PNG = 'PNG';
    const TYPE_GIF = 'GIF';

    const CONTENT_HTML = 'text/html';
    const CONTENT_TEXT = 'text/plain';
    const CONTENT_PNG = 'image/png';
    const CONTENT_GIF = 'image/gif';
    const CONTENT_JPEG = 'image/jpeg';
    const CONTENT_JSON = 'application/json';
    const CONTENT_XML = 'application/xml';
    const CONTENT_INI = 'text/ini';
    const CONTENT_BINARY = 'application/octet-stream';

    public $type;
    public $data;
    public $meta;
    public $contentType;

    public function __construct($type, $data, $meta, $contentType = self::CONTENT_HTML) {
        $this->type = $type;
        $this->data = $data;
        $this->meta = $meta;

        $this->contentType = $contentType;
    }

}