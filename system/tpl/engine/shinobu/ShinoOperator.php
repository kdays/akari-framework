<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/21
 * Time: 下午1:14
 */

namespace Akari\system\tpl\engine\shinobu;


class ShinoOperator {

    public static function getBaseOper() {
        return ['>', '<', '!', '!=', '>=', '<=', '!==', '===', '==', '..', '+', '-','+=', '-=', 'and', 'or'];
    }

    public static function getFuncOper() {
        return ['for', 'if', 'else', 'elif', 'set', 'endfor', 'endif'];
    }

}