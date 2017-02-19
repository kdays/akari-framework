<?php

namespace Akari\utility;

use Akari\config\ConfigItem;
use Akari\Context;
use Akari\system\Plugin;
use Akari\system\tpl\TemplateUtil;

class PageHelper extends Plugin
{
    public $pageSize = 10;
    public $totalRecord = 0;
    public $params = [];
    public $skipParams = [];
    public $display = 5;
    public $baseUrl;

    public $name;
    public $totalPage = null;
    public $currentPage = null;
    public $nextPage = null;
    public $prevPage = null;
    public $firstPage = null;
    public $lastPage = null;
    public $pagination = [];

    public $widgetName;

    protected static $m = [];

    /**
     * @param string $name
     *
     * @return PageHelper
     */
    public static function getInstance($name = 'default')
    {
        if (!isset(self::$m[$name])) {
            $helper = new self();
            $helper->name = $name;
            $helper->widgetName = Context::env(ConfigItem::DEFAULT_PAGE_TEMPLATE, null, 'Pager');

            self::$m[$name] = $helper;
        }

        return self::$m[$name];
    }

    public function setCurrentPage($page)
    {
        $this->currentPage = $page;

        return $this;
    }

    public function setPageSize($pageSize)
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    public function setTotalCount($total)
    {
        $this->totalRecord = $total;

        return $this;
    }

    public function setUrl($url)
    {
        $this->baseUrl = $url;

        return $this;
    }

    public function makeUrl(array $skip, $page = 1)
    {
        $url = parse_url($this->baseUrl);
        $query = array_key_exists('query', $url) ? $url['query'] : '';

        $result = [];
        parse_str($query, $result);
        foreach ($skip as $k) {
            unset($result[$k]);
        }

        $params = ['page' => $page] + $this->params + $result;

        return $this->url->get($url['path'], $params);
    }

    public function execute()
    {
        $totalPage = intval(ceil($this->totalRecord / $this->pageSize));
        if (!$totalPage) {
            $totalPage = 1;
        }

        if ($totalPage < $this->currentPage) {
            $this->currentPage = $totalPage;
        }

        $this->totalPage = $totalPage;

        $currentPage = $this->currentPage;
        $pagination = [];
        $skip = $this->skipParams;

        if ($this->totalPage > 0) {
            $left = ceil($this->display / 2);
            $right = ceil($this->display / 2) + 1;

            for ($i = 0; $i < $left; $i++) {
                $k = $currentPage - $i;
                if ($k > 0) {
                    $pagination[$k] = $this->makeUrl($skip, $k);
                } else {
                    break;
                }
            }

            for ($i = 1; $i < $right; $i++) {
                $k = $currentPage + $i;
                if ($k <= $this->totalPage) {
                    $pagination[$k] = $this->makeUrl($skip, $k);
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

        $this->firstPage = $this->makeUrl($skip, 1);
        $this->lastPage = $this->makeUrl($skip, $this->totalPage);
        $this->pagination = $pagination;

        return $this;
    }

    public function setSkipParams(array $skip)
    {
        $this->skipParams = $skip;

        return $this;
    }

    /**
     * 绑定替换队列.
     *
     * @param $key
     * @param $value
     * @param bool $addToUrl
     *
     * @return $this
     */
    public function bindValue($key, $value, $addToUrl = false)
    {
        if ($addToUrl) {
            if (!array_key_exists($key, $this->params)) {
                $this->baseUrl .= in_string($this->baseUrl, '?') ? '&' : '?';
                $this->baseUrl .= "$key=($key)";
            }
        }

        $this->params[$key] = $value;

        return $this;
    }

    /**
     * 同时设置多项，通过Skip可以跳过.
     *
     * @param array $params
     * @param array $skip
     * @param array $allow
     * @param bool  $addToUrl
     *
     * @return $this
     */
    public function bindValues(array $params, $skip = [], $allow = null, $addToUrl = false)
    {
        foreach ($params as $key => $value) {
            if (in_array($key, $skip)) {
                continue;
            }

            if ($allow !== null && !in_array($key, $allow)) {
                continue;
            }

            $this->bindValue($key, $value, $addToUrl);
        }

        return $this;
    }

    public function setWidget($tplId)
    {
        $this->widgetName = $tplId;

        return $this;
    }

    public function getHTML()
    {
        return TemplateUtil::load_widget($this->widgetName, $this);
    }

    public function needPage()
    {
        return $this->totalPage > 1;
    }

    public function getStart()
    {
        $start = ($this->currentPage - 1) * $this->pageSize;
        if ($start < 0) {
            return 0;
        }

        return $start;
    }

    public function getLength()
    {
        return $this->pageSize;
    }
}
