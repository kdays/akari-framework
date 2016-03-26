<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:32
 */

namespace Akari\system\exception;

use Akari\akari;
use Akari\Context;
use Akari\system\http\HttpCode;
use Akari\system\http\Response;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;

Class DefaultExceptionHandler extends BaseExceptionHandler {

    /**
     * @param \Exception $ex
     * @return \Akari\system\result\Result
     */
    public function handleException(\Exception $ex) {
        $this->response->setStatusCode(HttpCode::INTERNAL_SERVER_ERROR);

        $view = function($path, $data) {
            ob_start();
            @extract($data, EXTR_PREFIX_SAME, 'a_');
            include(AKARI_PATH. "/template/". $path. ".php");
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        };

        list($fileLine, $trace) = $this->crash($ex->getFile(), $ex->getLine(), $ex->getTrace());
        foreach ($fileLine as &$value) {
            $value = str_replace("  ", "<span class='w-block'></span>", $value);
        }

        $result = [
            'message' => $ex->getMessage(),
            'file' => str_replace(Context::$appBasePath, '', $ex->getFile()),
            'fileLine' => $fileLine,
            'line' => $ex->getLine(),
            'className' => get_class($ex),
            'trace' => $trace,
            'version' => akari::getVersion(),
            'build' => AKARI_BUILD
        ];

        return $this->_genHTMLResult( $view("DefaultException", $result) );
    }

}