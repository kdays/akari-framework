<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 14:45
 */

namespace Akari\utility\template;

use Akari\Context;
use Akari\system\security\Security;

Class Csrf implements BaseTemplateModule {

    public function run($arg = '') {
        $tokenName = Context::$appConfig->csrfTokenName;
        $token = Security::getCSRFToken();
        echo "<input type=\"hidden\" name=\"$tokenName\" value=\"$token\" />\n";
    }

}