<?php
namespace Akari\utility;

use Akari\config\ConfigItem;
use Akari\Context;
use Akari\system\ioc\DI;
use Akari\system\tpl\engine\BaseTemplateEngine;
use Akari\system\tpl\TemplateCommand;

Class PageHelper {

    public $pageSize = 10;
    public $totalRecord = 0;
    public $params = [];
    public $url;
    public $display = 10;
    
    public $mixedUrl;
    public $name;

    public $totalPage = NULL;
    public $currentPage = NULL;
    public $nextPage = NULL;
    public $prevPage = NULL;
    public $firstPage = NULL;
    public $lastPage = NULL;
    public $pagination = [];

    public $widgetName;

    protected static $m = [];

    /**
     * @param string $name
     * @return PageHelper
     */
    public static function getInstance($name = 'default'){
        if(!isset(self::$m[$name])){
            $helper = new self();
            $helper->name = $name;
            $helper->widgetName = Context::env(ConfigItem::DEFAULT_PAGE_TEMPLATE, NULL, 'Pager');

            self::$m[$name] = $helper;
        }

        return self::$m[$name];
    }

    public function setCurrentPage($page) {
        $this->currentPage = $page;
        return $this;
    }

    public function setPageSize($pageSize) {
        $this->pageSize = $pageSize;
        return $this;
    }

    public function setTotalCount($total) {
        $this->totalRecord = $total;
        return $this;
    }

    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    public function makeUrl(array $skip, $page = 1) {
        $url = $this->url;
        $params = $this->params + ["page" => $page];

        foreach ($params as $k => $v) {
            $url = str_replace('('. $k. ')', $v, $url);
        }

        $url = parse_url($url);
        $query = array_key_exists('query', $url) ? $url['query'] : '';

        $result = [];
        parse_str($query, $result);

        foreach ($skip as $k) unset($result[$k]);

        $resultUrl = make_url($url['path'], $result);
        return in_string($result, "?") ? $resultUrl : $resultUrl. "?";
    }

    public function execute(){
        $totalPage = intval(ceil($this->totalRecord / $this->pageSize));
        $this->totalPage = $totalPage;

        if (!$totalPage) {
            $totalPage = 1;
        }

        if ($totalPage < $this->currentPage) {
            $this->currentPage = $totalPage;
        }

        return $this;
    }

    /**
     * 绑定替换队列，但不会设置URL
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function bindValue($key, $value) {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * 单项设置URL
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function addUrlParam($key, $value) {
        if(!array_key_exists($key, $this->params)){
            $this->url .=  in_string($this->url, "?") ? "&" : "?";
            $this->url .= "$key=($key)";
        }

        $this->params[$key] = $value;
        return $this;
    }

    /**
     * 同时设置多项，通过Skip可以跳过
     *
     * @param array $params
     * @param array $skip
     * @param array $allow
     * @return $this
     */
    public function addUrlParams(array $params, $skip = [], $allow = NULL) {
        foreach ($params as $key => $value) {
            if (in_array($key, $skip)) {
                continue;
            }

            if ($allow !== NULL && !in_array($key, $allow)) {
                continue;
            }

            $this->addUrlParam($key, $value);
        }

        return $this;
    }

    public function setWidget($tplId) {
        $this->widgetName = $tplId;
        return $this;
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
        return TemplateCommand::widgetAction($engine, $this->widgetName, $this, True);
    }

    public function needPage() {
        return $this->totalPage > 1;
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