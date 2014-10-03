<?php
namespace Akari\system\result;

use Akari\config\ConfigItem;
use Akari\utility\TemplateHelper;

Class TemplateResult extends Result {

    var $bindArr = [];
    var $tplName = NULL;

    public function __construct($tplName, $bindArr = []) {
        $this->tplName = $tplName;
        $this->bindArr = $bindArr;

        return $this;
    }

    public function doProcess() {
        if (is_string($this->bindArr)) {
            C(ConfigItem::customLayout, $this->bindArr);
        }

        $arr = assign(NULL, NULL);
        if (is_array($this->bindArr))	$arr =  array_merge($arr, $this->bindArr);

        @extract($arr, EXTR_PREFIX_SAME, 'a_');
        require TemplateHelper::load($this->tplName);
    }

}