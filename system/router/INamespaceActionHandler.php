<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/12/18
 * Time: 上午12:36
 */

namespace Akari\system\router;

use Akari\system\http\Request;

interface INamespaceActionHandler {

    public static function handleAppActionNs(Request $request);

}
