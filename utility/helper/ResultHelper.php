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
use Akari\system\result\Result;
use Akari\system\router\Dispatcher;

trait ResultHelper {

    protected static function _genFileDownloadResult($fileContent, $fileName) {
        return new Result(Result::TYPE_DOWN, $fileContent, [
            'name' => $fileName
        ], Result::CONTENT_BINARY);
    }

    protected static function _genJSONResult($data = [], $contentType = Result::CONTENT_JSON) {
        return new Result(Result::TYPE_JSON, $data, NULL, $contentType);
    }

    protected static function _genHTMLResult($html, $contentType = Result::CONTENT_HTML) {
        return new Result(Result::TYPE_HTML, $html, NULL, $contentType);
    }

    protected static function _genTEXTResult($text) {
        return new Result(Result::TYPE_TEXT, $text, NULL, Result::CONTENT_TEXT);
    }

    protected static function _genJPEGResult($resource, $quality) {
        return new Result(Result::TYPE_JPEG, $resource, ['quality' => $quality], Result::CONTENT_JPEG);
    }

    public static function _genTplResult($data = [], $screenPath = NULL, $layoutPath = NULL, $contentType = Result::CONTENT_HTML) {
        if ($screenPath == NULL || $layoutPath == NULL) {
            $screenName = str_replace('.php', '', trim(Context::$appEntryName));

            if (Context::$appEntryMethod !== NULL) {
                $screenName = substr($screenName, 0, strlen($screenName) - strlen('Action'));
                $screenName .= DIRECTORY_SEPARATOR. Context::$appEntryMethod;
            }

            $screenName = strtolower($screenName);
            $suffix = Context::$appConfig->templateSuffix;

            if ($screenPath == NULL) {
                $screenPath = Dispatcher::getInstance()->findWay($screenName, 'template/view/', $suffix);
                $screenPath = str_replace([Context::$appEntryPath, $suffix, '/template/view/'], '', $screenPath);
            }

            if ($layoutPath == NULL) {
                $layoutPath = Dispatcher::getInstance()->findWay($screenName, 'template/layout/', $suffix);
                $layoutPath = str_replace([Context::$appEntryPath, $suffix, '/template/layout/'], '', $layoutPath);
            }

        }

        if ($screenPath == '') {
            throw new \Exception("auto template detect cannot found file or screen path is empty.");
        }

        return new Result(Result::TYPE_TPL, $data, [
            "view" => $screenPath,
            "layout" => $layoutPath
        ], $contentType);
    }

    protected static function _redirect($uri, $code = HttpCode::FOUND) {
        Response::getInstance()->setStatusCode($code);

        Header("Location: ". $uri);
        return self::_genNoneResult();
    }

    protected static function _genNoneResult() {
        return new Result(Result::TYPE_NONE, NULL, NULL);
    }

}
