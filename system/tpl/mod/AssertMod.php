<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/10
 * Time: 上午8:25
 */

namespace Akari\system\tpl\mod;


class AssertMod implements BaseTemplateMod{

    public function run($args = '') {
        // 首先判断前面空格是否有CSS/JS的指定 有的话必须实现
        $cmds = explode(" ", $args);
        $cmd = strtolower(array_pop($cmds));

        if ($cmd == 'js') {
            return $this->parseJs(implode(" ", $cmds));
        } elseif ($cmd == 'css') {
            return $this->parseCss(implode(" ", $cmds));
        }
        
        if (stripos($args, ".js") !== false) {
            return $this->parseJs($args);
        } else {
            return $this->parseCss($args);
        }
    }
    
    protected function parseJs($url) {
        return sprintf('<script type="text/javascript" src="%s"></script>', $url);
    }
    
    protected function parseCss($url) {
        return sprintf('<link rel="stylesheet" href="%s" />', $url);
    }

}