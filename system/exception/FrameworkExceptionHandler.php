<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/30
 * Time: 10:16
 */

namespace Akari\system\exception;

use \Exception;
use Akari\Context;
use Akari\system\http\HttpCode;
use Akari\system\router\NotFoundURI;

/**
 * Class FrameworkExceptionHandler
 * 分发器异常的处理，没有找到URI会转发到本异常处理器
 * 相关配置可见config，主要是页面找不到时默认地址
 *
 * @package Akari\system\exception
 */
class FrameworkExceptionHandler extends BaseExceptionHandler {

    public function handleException(Exception $ex) {
        $config = Context::$appConfig;

        // 调用框架的模板
        $view = function ($path, $data) {
            ob_start();
            @extract($data, EXTR_PREFIX_SAME, 'a_');
            include AKARI_PATH . '/template/' . $path . '.php';
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        };

        // CLI模式时为了方便调试 任何错误不捕获时全调用
        if (CLI_MODE) {
            echo "\n-------\n\033[31;49;1m" . $ex->getMessage() . "\n" . get_class($ex) . "\033[39;49;0m";

            echo "\n\n" . $this->getMagicTraceString($ex) . "\n";
            die;
        }

        switch (get_class($ex)) {

            // 没有找到URI
            case NotFoundURI::class:
                $this->response->setStatusCode(HttpCode::NOT_FOUND);

                $msg = $ex->getMessage();
                if ($ex->getPrevious() !== NULL) {
                    $msg = $ex->getPrevious()->getMessage();
                }

                $message = [
                    'msg' => $msg,
                    "url" => Context::$uri,
                    "index" => Context::$appConfig->appBaseURL
                ];

                if (!empty($config->notFoundTemplate)) {
                    return self::_genTplResult($message, NULL, $config->notFoundTemplate);
                } else {
                    // 处理$ex
                    return self::_genHTMLResult( $view(404, $message) );
                }

            // 系统的fatal
            case FatalException::class:
                $this->response->setStatusCode(HttpCode::INTERNAL_SERVER_ERROR);
                $message = [
                    "message" => $ex->getMessage(),
                    "file" => basename($ex->getFile()) . ":" . $ex->getLine()
                ];

                if (ob_get_length()) ob_clean();

                if (!empty($config->serverErrorTemplate)) {
                    return self::_genTplResult($message, NULL, $config->serverErrorTemplate);
                } else {
                    return self::_genHTMLResult( $view(500, $message) );
                }
        }

    }



}
