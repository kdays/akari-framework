<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/9/19
 * Time: 下午2:55
 */

namespace Akari\utility;

use Akari\Context;
use Akari\system\Plugin;
use Akari\config\ConfigItem;
use Akari\system\tpl\TemplateUtil;

class Pagination extends Plugin {

    private $totalRecord = 0;
    private $pageSize = 10;
    private $display = 5;

    private $parameterName = "page";
    private $widget;

    public $baseUrl;
    public $urlArgs = [];
    public $viewArgs = [];

    public $currentPage = 1;

    // 相关URL
    public $firstPage;
    public $lastPage;
    public $nextPage;
    public $prevPage;
    public $totalPage;
    public $pagination = [];

    public function __construct($currentPage, $baseUrl = NULL, $urlArgs = NULL) {
        if ($urlArgs === NULL) {
            $urlArgs = $_GET;
        }

        if ($baseUrl === NULL) {
            $baseUrl = $this->request->getUrlPath();
        }

        $this->baseUrl = $baseUrl;
        $this->urlArgs = $urlArgs;
        $this->widget = Context::env(ConfigItem::DEFAULT_PAGE_TEMPLATE, NULL, 'Pager');
        $this->currentPage = $currentPage;
    }

    public function setWidget($widget) {
        $this->widget = $widget;

        return $this;
    }

    public function bindVar($key, $value) {
        $this->viewArgs[$key] = $value;
    }

    public function setUrlArgs($commArgs) {
        $this->urlArgs = $commArgs;

        return $this;
    }

    /**
     * 获得分页标示的URL名称
     * 
     * @param string $name
     * @return $this
     */
    public function setPagerParameterName($name) {
        $this->parameterName = $name;

        return $this;
    }

    public function setTotal($totalRecord) {
        $this->totalRecord = $totalRecord;

        return $this;
    }

    public function setDisplayRange($display) {
        $this->display = $display;

        return $this;
    }

    public function makeUrl($page, $except = []) {
        $url = $this->baseUrl;
        $args = $this->urlArgs;

        if (in_string($url, "(page)")) {
            $url = str_replace("(page)", $page, $url);

            $args = [];
            mb_parse_str(mb_parse_url($url, PHP_URL_QUERY), $args);
            if (!empty($except)) {
                foreach ($except as $k) unset($args[$k]);
            }

            return make_url(mb_parse_url($url, PHP_URL_PATH), $args);
        }

        if (!empty($except)) {
            foreach ($except as $k) unset($args[$k]);
        }
        $args = [$this->parameterName => $page] + $args;

        return $this->url->get($url, $args);
    }

    public function getTotal() {
        return $this->totalRecord;
    }

    public function getCurrentPage() {
        return $this->currentPage;
    }

    public function setPageSize($pageSize) {
        $this->pageSize = $pageSize;
    }

    public function getPageSize() {
        return $this->pageSize;
    }

    public function getSkip() {
        return ($this->currentPage - 1) * $this->pageSize;
    }

    public function getLimit() {
        return $this->getPageSize();
    }

    public function getTotalPage() {
        $r = intval(ceil($this->totalRecord / $this->pageSize));

        return empty($r) ? 1 : $r;
    }

    public function _execute() {
        $totalPage = $this->getTotalPage();
        if ($totalPage < $this->currentPage) {
            $this->currentPage = $totalPage;
        }
        $currentPage = $this->currentPage;

        $pagination = [];
        if ($totalPage > 0) {
            $left = ceil($this->display / 2);
            $right = ceil($this->display / 2) + 1;

            for ($i = 0; $i < $left; $i++) {
                $k = $currentPage - $i;
                if ($k > 0) {
                    $pagination[$k] = $this->makeUrl($k);
                } else {
                    break;
                }
            }

            for ($i = 1; $i < $right; $i++) {
                $k = $currentPage + $i;
                if ($k <= $totalPage) {
                    $pagination[$k] = $this->makeUrl($k);
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

        $this->totalPage = $totalPage;
        $this->firstPage = $this->makeUrl(1);
        $this->lastPage = $this->makeUrl($totalPage);
        $this->pagination = $pagination;
    }

    public function render() {
        $this->_execute();

        return TemplateUtil::load_widget($this->widget, $this);
    }

    public function needPage() {
        return $this->getTotalPage() > 1;
    }
}
