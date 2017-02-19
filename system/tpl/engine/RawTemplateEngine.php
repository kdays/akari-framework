<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午3:52.
 */

namespace Akari\system\tpl\engine;

class RawTemplateEngine extends BaseTemplateEngine
{
    public function parse($tplPath, array $data, $type, $onlyCompile = false)
    {
        return $onlyCompile ? $tplPath : $this->_getView($tplPath, $data);
    }

    public function getResult($layoutResult, $screenResult)
    {
        if (empty($screenResult)) {
            return $layoutResult;
        }

        $screenCmd = $this->getOption('screenCmdMark', '#SCREEN#');

        return str_replace($screenCmd, $screenResult, $layoutResult);
    }
}
