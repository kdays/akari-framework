<?php
namespace Akari\utility;

use Akari\config\ConfigItem;
use Akari\system\ioc\DI;
use Akari\system\tpl\engine\BaseTemplateEngine;
use Akari\system\tpl\mod\BaseTemplateMod;
use Akari\system\tpl\TemplateCommand;
use Akari\system\tpl\TemplateHelper;

Class PageHelper {

    public $pageSize = 10;
    public $totalRecord = 0;
    public $params = [];
    public $url;
    public $display = 10;
    public $mixedUrl;
    public $objId;

    public $totalPage = NULL;
    public $currentPage = NULL;
    public $nextPage = NULL;
    public $prevPage = NULL;
    public $firstPage = NULL;
    public $lastPage = NULL;
    public $pagination = [];

    public $template = 'pager';

    protected static $m = [];

    /**
     * @param string $name
     * @return PageHelper
     */
    public static function getInstance($name = 'default'){
        if(!isset(self::$m[$name])){
            self::$m[ $name ] = new self();
            self::$m[ $name ]->objId = $name;
            self::$m[ $name ]->template = C(ConfigItem::DEFAULT_PAGE_TEMPLATE, NULL, 'pager');
        }

        return self::$m[ $name ];
    }

    public function init($url, $nowPage, $totalRecord, array $extraParam = [], $pageSize = 20){
        $this->currentPage = $nowPage;
        $this->pageSize = $pageSize;
        $this->totalRecord = $totalRecord;

        $this->params = $extraParam;
        $this->url = $url;

        $totalPage = intval(ceil($totalRecord / $pageSize));
        $this->totalPage = $totalPage;

        if (!$totalPage) {
            $totalPage = 1;
        }

        if ($totalPage < $this->currentPage) {
            $this->currentPage = $totalPage;
        }

        return $this;
    }

    public function addParam($key, $value) {
        if(!array_key_exists($key, $this->params)){
            $this->url .=  in_string($this->url, "?") ? "&" : "?";
            $this->url .= "$key=($key)";
        }

        $this->params[$key] = $value;
    }

    public function setParam($key, $value) {
        $this->params[$key] = $value;
    }

    public function addParams(array $params) {
        foreach ($params as $key => $value) {
            $this->addParam($key, $value);
        }
    }

    public function setTemplate($tplId) {
        $this->template = $tplId;
    }

    public function getHTML() {
        $url = $this->url;
        foreach ($this->params as $k => $v) {
            $url = str_replace('('. $k. ')', $v, $url);
        }
        $this->mixedUrl = $url;

        $currentPage = $this->currentPage;
        $pagination = [];
        if ($this->totalPage > 0) {
            $left = ceil($this->display / 2);
            $right = ceil($this->display / 2) + 1;

            for ($i = 0; $i < $left; $i++) {
                $k = $currentPage - $i;
                if ($k > 0) {
                    $pagination[$k] = str_replace('(page)', $k, $url);
                } else {
                    break;
                }
            }

            for ($i = 1; $i < $right; $i++) {
                $k = $currentPage + $i;
                if ($k <= $this->totalPage) {
                    $pagination[$k] = str_replace('(page)', $k, $url);
                } else {
                    break;
                }
            }

            ksort($pagination);
        }

        if (array_key_exists($currentPage + 1, $pagination)) {
            $this->nextPage = $pagination[$currentPage + 1];
        }

        if (array_key_exists($currentPage - 1, $pagination)) {
            $this->prevPage = $pagination[$currentPage - 1];
        }

        $this->firstPage = str_replace('(page)', 1, $url);
        $this->lastPage = str_replace('(page)', $this->totalPage, $url);
        $this->pagination = $pagination;

        /** @var BaseTemplateEngine $templateHelper */
        $engine = DI::getDefault()->getShared('viewEngine');
        return TemplateCommand::widgetAction($engine, $this->template, $this);
    }

    public function needPage() {
        return $this->currentPage > 1;
    }

    public function getStart() {
        $start = ($this->currentPage - 1) * $this->pageSize;
        if ($start < 0) return 0;
        return $start;
    }

    public function getLength() {
        return $this->pageSize;
    }
}