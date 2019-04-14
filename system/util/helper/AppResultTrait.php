<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-25
 * Time: 18:54
 */

namespace Akari\system\util\helper;

use Akari\system\http\HttpCode;
use Akari\system\result\Result;

/**
 * Trait AppResultTrait
 * @package Akari\system\util\helper
 *
 * @property \Akari\system\view\View $view
 * @property \Akari\system\http\Response $response
 */
trait AppResultTrait {

    public function _genTplResult(array $data, string $viewName = NULL, string $layoutName = NULL) {
        return new Result(Result::TYPE_VIEW, $data, [
            'layout' => $layoutName,
            'view' => $viewName
        ]);
    }

    public function _genHTMLResult(string $text) {
        return new Result(Result::TYPE_HTML, $text, NULL);
    }

    public function _genJSONResult(array $result, $jsonp = NULL) {
        return new Result(Result::TYPE_JSON, $result, [
            'jsonp' => $jsonp
        ], 'application/javascript');
    }

    public function _genNoneResult() {
        return new Result(Result::TYPE_CUSTOM, NULL, NULL);
    }

    public function _redirect(string $uri, int $httpCode = HttpCode::FOUND) {
        $this->response->redirect($uri, $httpCode);

        return $this->_genNoneResult();
    }

}
