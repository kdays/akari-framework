<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午9:36
 */

namespace Akari\system\tpl\mod;


use Akari\Context;
use Akari\system\security\Security;

class CsrfMod implements BaseTemplateMod {

    public function run($args = '') {
        $tokenName = Context::$appConfig->csrfTokenName;
        
        if ($tokenName) {
            $token = Security::getCSRFToken();
            return sprintf('<input type="hidden" name="%s" value="%s" />', $tokenName, $token). "\n";
        }
    }

}