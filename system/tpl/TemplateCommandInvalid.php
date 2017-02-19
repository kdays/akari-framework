<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/27
 * Time: 下午2:39.
 */

namespace Akari\system\tpl;

use Akari\Context;
use Akari\system\exception\AkariException;

class TemplateCommandInvalid extends AkariException
{
    public function __construct($commandName, $args, $file = null)
    {
        $file = str_replace(Context::$appEntryPath, '', $file);
        $this->message = sprintf('Template Command Invalid: [ %s ] with [ %s ] on [ %s ]', $commandName, var_export($args, true), $file);
    }
}
