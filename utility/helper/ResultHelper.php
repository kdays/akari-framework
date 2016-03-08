<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:38
 */

namespace Akari\utility\helper;

use Akari\Context;
use Akari\system\console\Request;
use Akari\system\http\HttpCode;
use Akari\system\http\Response;
use Akari\system\ioc\DI;
use Akari\system\result\Result;
use Akari\system\router\Dispatcher;

trait ResultHelper {

    public static function _setFileToSend($fileContent, $fileName) {
        return new Result(Result::TYPE_CUSTOM, $fileContent, ['name' => $fileName], 
            Result::CONTENT_BINARY, function($result) {
                /** @var Response $resp */
                $resp = DI::getDefault()->getShared("response");
                $resp
                    ->setHeader('Accept-Ranges', 'bytes')
                    ->setHeader('Content-Disposition', "attachment; filename=". urlencode($result->meta['name']));
    
                return TRUE;
            });
    }

    public static function _genJSONResult($data = [], $contentType = Result::CONTENT_JSON) {
        return new Result(Result::TYPE_JSON, $data, NULL, $contentType);
    }

    public static function _genXMLResult($data = [], $contentType = Result::CONTENT_XML) {
        return new Result(Result::TYPE_XML, $data, NULL, $contentType);
    }

    public static function _genINIResult($data = [], $contentType = Result::CONTENT_INI) {
        return new Result(Result::TYPE_INI, $data, NULL, $contentType);
    }

    public static function _genHTMLResult($html, $contentType = Result::CONTENT_HTML) {
        return new Result(Result::TYPE_HTML, $html, NULL, $contentType);
    }

    public static function _genTEXTResult($text) {
        return new Result(Result::TYPE_TEXT, $text, NULL, Result::CONTENT_TEXT);
    }

    public static function _genJPEGResult($resource, $quality) {
        return new Result(Result::TYPE_JPEG, $resource, ['quality' => $quality], Result::CONTENT_JPEG);
    }

    public static function _genPNGResult($resource, $quality) {
        return new Result(Result::TYPE_PNG, $resource, ['quality' => $quality], Result::CONTENT_PNG);
    }

    public static function _genTplResult($data = [], $screenPath = NULL, $layoutPath = NULL, $contentType = Result::CONTENT_HTML) {
        return new Result(Result::TYPE_TPL, $data, [
            "view" => $screenPath,
            "layout" => $layoutPath
        ], $contentType);
    }

    public static function _alertRedirect($message, $uri = NULL, $encoding = "utf-8") {
        $js = "history.back(-1)";
        if ($uri !== NULL) {
            $js = "location.href='$uri'";
        }

        $html = "<!DOCTYPE HTML><head><meta charset='". $encoding. "' /></head><body>";
        $html.= sprintf("<script>alert('%s');%s;</script>", $message, $js);
        $html.= "</body>";

        return new Result(Result::TYPE_HTML, $html, Result::CONTENT_HTML);
    }

    public static function _redirect($uri, $code = HttpCode::FOUND) {
        /** @var Response $response */
        $response = DI::getDefault()->getShared("response");
        $response->setStatusCode($code);

        Header("Location: ". $uri);
        return self::_genNoneResult();
    }

    public static function _genNoneResult() {
        return new Result(Result::TYPE_NONE, NULL, NULL);
    }

    public static function _genCustomResult($data, $meta, $contentType = Result::CONTENT_BINARY, callable $callback = NULL) {
        return new Result(Result::TYPE_CUSTOM, $data, $meta, $contentType, $callback);
    }

}
