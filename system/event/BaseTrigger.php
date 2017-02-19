<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 09:18.
 */

namespace Akari\system\event;

use Akari\system\ioc\Injectable;
use Akari\system\result\Result;
use Akari\utility\helper\ExceptionSetter;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\ValueHelper;

abstract class BaseTrigger extends Injectable
{
    use ResultHelper, Logging, ExceptionSetter, ValueHelper;

    protected function stop()
    {
        throw new StopEventBubbling();
    }

    /**
     * 处理规则请求器.
     *
     * @param Result|null $result
     *
     * @return Result
     */
    abstract public function process(Result $result = null);
}
