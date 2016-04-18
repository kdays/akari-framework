<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午9:44
 */

namespace Akari\system\tpl\mod;


use Akari\utility\PageHelper;

class PageMod extends BaseTemplateModule {

    public function run($args = 'default') {
        return PageHelper::getInstance($args)->getHTML();
    }

}