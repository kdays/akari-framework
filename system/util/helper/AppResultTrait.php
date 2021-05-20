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

    /**
     * @param array $data
     * @param string|NULL $viewName
     * @param string|NULL $layoutName
     * @param bool $mergeParentVars 如果设置为false时，view渲染这个模板时，data仅渲染提供的，不会采纳view之前提交的数据
     * @return Result
     */
    public function _genTplResult(array $data, string $viewName = NULL, string $layoutName = NULL, $mergeParentVars = TRUE) {
        return new Result(Result::TYPE_VIEW, $data, [
            'layout' => $layoutName,
            'view' => $viewName,
            'merge_vars' => $mergeParentVars ? 1 : 0
        ]);
    }

    public function _genHTMLResult(string $text) {
        return new Result(Result::TYPE_HTML, $text, NULL);
    }

    public function _genJSONResult(array $result, $jsonp = NULL) {
        return new Result(Result::TYPE_JSON, $result, [
            'jsonp' => $jsonp
        ], Result::CONTENT_JSON);
    }

    public function _genDownloadResult(string $data, string $name) {
        return $this->_genCUSTOMResult($data, ['name' => $name], Result::CONTENT_BINARY, function (Result $result) {
            $this->response->setHeader('Content-Disposition', 'attachment;filename=' . $result->meta['name']);
            $this->response->setHeader('Content-Transfer-Encoding', 'binary');
        });
    }

    public function _genCUSTOMResult($data, array $meta, string $contentType, callable $callback) {
        return new Result(Result::TYPE_CUSTOM, $data, $meta, $contentType, $callback);
    }

    public function _genNoneResult() {
        return new Result(Result::TYPE_CUSTOM, NULL, NULL);
    }

    public function _redirect(string $uri, int $httpCode = HttpCode::FOUND) {
        $this->response->redirect($uri, $httpCode);

        return $this->_genNoneResult();
    }

}
