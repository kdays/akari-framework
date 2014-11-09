<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/11/5
 * Time: 22:34
 */

namespace Akari\system\module;

use Akari\utility\PageHelper;

Class PageModule {

    protected static $m;
    public static function getInstance(){
        if(!isset(self::$m)){
            self::$m = new self();
        }
        return self::$m;
    }

    public function run($p = 'default'){
        $result = PageHelper::getInstance($p)->getHTML();
        echo $result;
    }

}