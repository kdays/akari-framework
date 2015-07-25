<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/14
 * Time: 下午10:41
 */

namespace Akari\system\tpl\engine;


use Akari\system\tpl\engine\shinobu\Lexer;

class ShinobuTemplateEngine extends BaseTemplateEngine {


    public function parse($tplPath, array $data, $type, $onlyCompile = false) {
        $this->engineArgs = $data;
        $cachePath = $this->getCachePath($tplPath);

        if (filemtime($tplPath) > filemtime($cachePath) || !file_exists($cachePath)) {
            $template = file_get_contents($tplPath);
            // {{ value }} -> {{ echo $value }}

            $that = $this;
            $lexer = new Lexer();
            $tokens = $lexer->tokenize($template);

            var_dump($tokens);die;
        }
    }

    public function getResult($layoutResult, $screenResult) {
        if (empty($screenResult)) {
            return $layoutResult;
        }

        return str_replace("{% screen %}", $screenResult, $layoutResult);
    }
}