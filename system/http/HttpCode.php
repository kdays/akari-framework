<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 20:28
 */

namespace Akari\system\http;

class HttpCode {

    const OK = 200;

    /**
     * 永久性的被移动到新位置
     * @var int
     */
    const MOVED_PERMANENTLY = 301;

    /**
     * 临时的转移，和301 MOVED_PERMANENTLY不同
     * @var int
     */
    const FOUND = 302;
    const BAD_REQUEST = 400;
    const UNAUTHORIZED = 401;
    const FORBIDDEN = 403;
    const NOT_FOUND = 404;
    const METHOD_NOT_ALLOWED = 405;
    const UNAVAILABLE_FOR_LEGAL_REASON = 451;

    const INTERNAL_SERVER_ERROR = 500;
    const SERVICE_UNAVAILABLE = 503;
    const GATEWAY_TIMEOUT = 504;

    public static $statusCode = array(
        "200" => "OK",
        "201" => "Created",
        "202" => "Accepted",
        "203" => "Non-Authoritative Information",
        "204" => "No Content",
        "205" => "Reset Content",
        "206" => "Partial Content",

        "300" => "Multiple Choices",
        "301" => "Moved Permanently",
        "302" => "Found",
        "303" => "See Other",
        "304" => "Not Modified",
        "305" => "Use Proxy",

        "400" => "Bad Request",
        "401" => "Unauthorized",
        "402" => "Payment Required",
        "403" => "Forbidden",
        "404" => "Not Found",
        "405" => "Method Not Allowed",
        "406" => "Not Acceptable",
        "407" => "Proxy Authentication",
        "408" => "Request Timeout",
        "409" => "Conflict",
        "410" => "Gone",
        "411" => "Length Required",
        "412" => "Precondition Failed",
        "413" => "Request Entity Too Large",
        "414" => "Request-URI Too Long",
        "415" => "Unsupported Media Type",
        "416" => "Requested Range Not Satisfiable",
        "417" => "Expectation Failed",

        "451" => "Unavailable For Legal Reasons",

        "500" => "Internal Server Error",
        "501" => "Not Implemented",
        "502" => "Bad Gateway",
        "503" => "Service Unavailable",
        "504" => "Gateway Timeout"
    );

}
