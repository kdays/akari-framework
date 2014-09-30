<?php
namespace Akari\utility;

use Akari\Context;

Class TemplateHelperCommand{
    public static function getScreen() {
        return TemplateHelper::load(Context::$lastTemplate, false);
    }

    public static function lang($id, $command = ''){
        $command = explode("&", $command);
	    $L = [];
        foreach($command as $value){
            $tmp = explode("=", $value);
            $L[$tmp[0]] = $tmp[1];
        }

        echo I18n::get($id, $L);
    }

    public static function template($id) {
        return TemplateHelper::load($id, false, false);
    }

    public static function panel($id) {
        return TemplateHelper::load("../partial/$id", false, false);
    }

    public static function module($id, $data = '') {
	    $id = ucfirst($id);
        $appPath = Context::$appBasePath.DIRECTORY_SEPARATOR.BASE_APP_DIR."/lib/{$id}Module.php";
        $corePath = Context::$appBasePath."/core/system/module/{$id}Module.php";

        if(file_exists($appPath)){
            $clsName = Context::$appBaseNS."\\lib\\{$id}Module";
        }elseif(file_exists($corePath)){
            $clsName = "Akari\\system\\module\\{$id}Module";
        }else{
            throw new \Exception("TemplateModule $id not found");
        }

        $clsObj = $clsName::getInstance();

        return $clsObj->run($data);
    }

    public static function widget($id) {
        $widgetPath = implode(DIRECTORY_SEPARATOR, [
            Context::$appBasePath, BASE_APP_DIR, "widget", $id.".php"
        ]);

        if (file_exists($widgetPath)) {
            $widgetResult = require($widgetPath);
            if (!empty($widgetResult)) {
                @extract($widgetResult);
                require T("../partial/".$id, FALSE);
            }
        } else {
            throw new \Exception("Template Widget $id not found");
        }
    }

    /**
     * 增加的关于addJS,addCSS的界面
     */
    public static function addJs($path) {
        TemplateHelper::addJs($path);
    }

    public static function addCss($path) {
        TemplateHelper::addCss($path);
    }
}