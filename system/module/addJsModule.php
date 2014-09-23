<?php
namespace Akari\system\module;

!defined("AKARI_PATH") && exit;

Class addJsModule {

    public $saved = [];
    protected static $m;
    public static function getInstance(){
        if(!isset(self::$m)){
            self::$m = new self();
        }
        return self::$m;
    }

    public function run($p){
        if ($p == "list") {
            foreach ($this->saved as $value) {
                echo "<script type=\"text/javascript\" src=\"$value\" />";
            }
        } else {
            $this->saved[] = $p;
        }
    }

}