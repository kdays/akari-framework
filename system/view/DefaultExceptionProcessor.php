<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-08
 * Time: 15:22
 */

namespace Akari\system\view;


use Akari\exception\ActionNotFound;
use Akari\system\security\BaseExceptionProcessor;
use Akari\system\util\ExceptionUtil;

class DefaultExceptionProcessor extends BaseExceptionProcessor {

    public function process(\Throwable $ex) {
        // TODO: Implement process() method.
        if ($ex instanceof ActionNotFound) {
            return $this->_genHTMLResult( View::render4Data( AKARI_PATH . "views/404.phtml", []) );
        }

        if (defined('APP_DEBUG') && APP_DEBUG) {
            /// traceback
            list($fileLines, $trace) = ExceptionUtil::getCrashDebugInfo($ex->getFile(), $ex->getLine(), $ex->getTrace());
            return $this->_genHTMLResult(
                View::render4Data(AKARI_PATH . "views/traceback.phtml", [
                    'ex' => $ex,
                    'fileLine' => $fileLines,
                    'line' => $ex->getLine(),
                    'trace' => $trace
                ])
            );
        }

        return $this->_genHTMLResult(
            View::render4Data(AKARI_PATH . "views/500.phtml", [
                'ex' => $ex
            ])
        );
    }
}
