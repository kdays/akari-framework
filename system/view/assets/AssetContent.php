<?php

namespace Akari\system\view\assets;

class AssetContent {


    public $content;
    public $type;

    public $options = [];
    public $htmlOptions = [];

    public static function init($type, $content, $options) {
        $d = new self();
        $d->content = $content;
        $d->type = $type;
        $d->options = $options;

        if (!empty($options['type']) && $options['type'] == 'module') {
            $d->htmlOptions['type'] = 'module';
        }

        return $d;
    }

    public function getHtmlOptions() {
        return $this->htmlOptions;
    }

}