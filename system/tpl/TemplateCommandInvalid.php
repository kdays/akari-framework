<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/27
 * Time: 下午2:39
 */

namespace Akari\system\tpl;

use Akari\Context;
use Akari\system\exception\AkariException;

class TemplateCommandInvalid extends ViewException {

    public function __construct($commandName, $args, $file = NULL) {
        $file = str_replace(Context::$appEntryPath, '', $file);
        $this->message = sprintf("Template Command Invalid: [ %s ] with [ %s ] on [ %s ]", $commandName, var_export($args, TRUE), $file);
    }

}
