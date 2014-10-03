<?php
namespace Akari\system\result;

use Akari\system\log\Logging;

Class Result {

    public function _log($message) {
        Logging::_log($message);
    }

}