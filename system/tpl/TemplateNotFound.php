<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/27
 * Time: 下午2:40
 */

namespace Akari\system\tpl;

use Akari\system\exception\AkariException;

class TemplateNotFound extends ViewException {

    public function __construct($template) {
        $this->message = sprintf("Not Found Template [ %s ]", $template);
    }

}
