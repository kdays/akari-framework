<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 14:54
 */

namespace Akari\utility\template;

use Akari\utility\PageHelper;

Class Pages implements BaseTemplateModule {

    public function run($pageId = 'default') {
        echo PageHelper::getInstance($pageId)->getHTML();
    }

}