<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/11
 * Time: 上午10:43.
 */

namespace Akari\system\logger;

use Akari\Context;
use Akari\system\event\Event;
use Akari\system\router\NotFoundURI;
use Akari\utility\helper\Logging;

class DefaultExceptionAutoLogger
{
    use Logging;

    public static function log(Event $event)
    {
        /** @var \Exception $ex */
        $ex = $event->getData();

        $level = AKARI_LOG_LEVEL_ERROR;
        if (isset($ex->logLevel)) {
            $level = $ex->logLevel;
        }

        if ($ex instanceof NotFoundURI) {
            return;
        }

        self::_log(
            sprintf('Message: %s File: %s',
                $ex->getMessage(),
                str_replace(Context::$appBasePath, '', $ex->getFile()).':'.$ex->getLine()),
            $level
        );
    }
}
