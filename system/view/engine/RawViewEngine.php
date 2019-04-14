<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-28
 * Time: 15:49
 */

namespace Akari\system\view\engine;


use Akari\system\view\View;

class RawViewEngine extends BaseViewEngine {

    public function parse($tplPath, array $data, $type, $onlyCompile = FALSE) {
        return $onlyCompile ? $tplPath : View::render4Data($tplPath, $data);
    }

    public function getResult($layoutResult, $screenResult) {
        if (empty($screenResult)) {
            return $layoutResult;
        }

        $screenCmd = $this->options['screenCmdMark'] ?? '#SCREEN#';
        return str_replace($screenCmd, $screenResult, $layoutResult);
    }


}
