<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 09:03
 */

namespace Akari\system\result;

use Akari\system\http\Response;
use Akari\utility\TemplateHelper;

Class Processor {

    protected static $p;
    public static function getInstance() {
        if (!isset(self::$p)) {
            self::$p = new self();
        }

        return self::$p;
    }

    protected function processPNG(Result $result) {
        imagepng($result->data);
        imagedestroy($result->data);
    }

    protected function processJPEG(Result $result) {
        if (isset($result->meta['quality'])) {
            imagejpeg($result->data, NULL, $result->meta['quality']);
        } else {
            imagejpeg($result->data);
        }
        imagedestroy($result->data);
    }

    protected function processGIF(Result $result) {
        imagegif($result->data);
        imagedestroy($result->data);
    }

    public function processJSON(Result $result) {
        echo json_encode($result->data);
    }

    public function processHTML(Result $result) {
        echo $result->data;
    }

    public function processTPL(Result $result) {
        $helper = TemplateHelper::getInstance();
        if (is_array($result->data))    $helper->assign($result->data, NULL);
        $template = $helper->load($result->meta['view'], $result->meta['layout']);

        echo $template;
    }

    public function processTEXT(Result $result) {
        echo $result->data;
    }

    public function processDOWNLOAD(Result $result) {
        $resp = Response::getInstance();
        $resp->setHeader('Accept-Ranges', 'bytes');
        $resp->setHeader('Content-Disposition', "attachment; filename=".$result->meta['name']);

        echo $result->data;
    }

    public function processNONE(Result $result) {

    }

    public function processResult(Result $result) {
        $method = "process".$result->type;
        $resp = Response::getInstance();

        $resp->setContentType($result->contentType);
        $resp->doOutput();

        $this->$method($result);
    }

}