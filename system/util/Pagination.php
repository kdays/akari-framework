<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-19
 * Time: 20:12
 */

namespace Akari\system\util;

use Akari\system\ioc\Injectable;

class Pagination extends Injectable {

    public $totalRecord = 0;
    public $pageSize = 10;
    public $display = 5;

    public $currentPage = 1;
    public $parameterName = 'page';
    public $viewName = 'pages';

    private $bindVars;
    private $baseUrl;
    private $urlArgs;

    public function __construct(int $currentPage, ?int $pageSize, ?int $totalRecord, ?string $baseUri, ?array $urlArgs = NULL) {
        $this->totalRecord = $totalRecord;
        $this->pageSize = $pageSize;
        $this->currentPage = $currentPage;

        $this->baseUrl = empty($baseUri) ? $this->router->resolveURI() : $baseUri;
        $this->urlArgs = $urlArgs;
    }

    public function getUrl(int $page) {
        $baseUri = $this->baseUrl;

        if (TextUtil::exists($baseUri, '(page)')) {
            return str_replace('(page)', $page, $baseUri);
        }

        $urlArgs = $this->urlArgs ?? [];
        $urlArgs[ $this->parameterName ] = $page;

        return $this->url->get($this->baseUrl, $urlArgs);
    }

    public function getTotalPage() {
        $r = intval(ceil($this->totalRecord / $this->pageSize));

        return empty($r) ? 1 : $r;
    }

    public function setExtraBindVars(array $extraVars) {
        $this->bindVars = $extraVars;
    }

    public function getBindVars() {
        $totalPage = $this->getTotalPage();
        if ($totalPage < $this->currentPage) {
            $this->currentPage = $totalPage;
        }

        $pagination = [];
        if ($totalPage > 0) {
            $left = ceil($this->display / 2);
            $right = ceil($this->display / 2) + 1;

            for ($i = 0; $i < $left; $i++) {
                $k = $this->currentPage - $i;
                if ($k > 0) {
                    $pagination[$k] = $this->getUrl($k);
                    continue;
                }
                break;
            }

            for ($i = 1; $i < $right; $i++) {
                $k = $this->currentPage + $i;
                if ($k <= $totalPage) {
                    $pagination[$k] = $this->getUrl($k);
                    continue;
                }
                break;
            }

            ksort($pagination);
        }

        $bindVars = [
            'totalPage' => $totalPage,
            'pageSize' => $this->pageSize,
            'totalRecord' => $this->totalRecord,
            'currentPage' => $this->currentPage,
            'pagination' => $pagination,
            'lastPage' => $this->getUrl($this->getTotalPage()),
            'firstPage' => $this->getUrl(1)
        ];

        if (array_key_exists($this->currentPage + 1, $pagination)) {
            $bindVars['nextPage'] = $pagination[$this->currentPage + 1];
        }

        if (array_key_exists($this->currentPage - 1, $pagination)) {
            $bindVars['prevPage'] = $pagination[$this->currentPage - 1];
        }

        return $bindVars;
    }

}
