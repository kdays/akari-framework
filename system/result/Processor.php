<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-08
 * Time: 15:45
 */

namespace Akari\system\result;

use Akari\system\ioc\Injectable;

class Processor extends Injectable {

    public function processHTML(Result $result) {
        return $result->data;
    }

    public function processVIEW(Result $result) {
        $layoutPath = $result->meta['layout'];
        $screenPath = $result->meta['view'];

        if ($screenPath !== NULL)   $this->view->setScreen($screenPath);
        if ($layoutPath !== NULL)   $this->view->setLayout($layoutPath);

        if ($result->meta['merge_vars'] == 1) {
            $result->data = array_merge($this->view->getVar(NULL), $result->data);
        }

        return $this->view->getResult($result->data);
    }

    public function processJSON(Result $result) {
        if (!empty($result->meta['jsonp'])) {
            return sprintf("%s(%s)", $result->meta['jsonp'], json_encode($result->data));
        }

        return json_encode($result->data);
    }

    public function processNONE(Result $result) {

    }

    public function processCUSTOM(Result $result) {
        return $result->data;
    }

    public function process(Result $result, $return = FALSE) {
        $method = "process" . $result->type;

        $this->response->setContentType($result->contentType);
        if (method_exists($this, $method)) {
            $resultCont = $this->$method($result);
            if ($return) {
                return $resultCont;
            }

            $this->response->setContent($resultCont);
        }
    }

}
