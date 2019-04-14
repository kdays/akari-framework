<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/27
 * Time: 下午2:49
 */

namespace Akari\system\cache\handler;

use Akari\exception\AkariException;

class CacheHandlerMethodNotSupport extends AkariException {

    public function __construct($handlerName, $methodName) {
        $this->message = "$handlerName CAN NOT USE METHOD: $methodName";
    }


}
