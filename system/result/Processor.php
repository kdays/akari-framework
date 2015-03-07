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

    protected function processJPEG(Result $result) {
        if (is_resource($result->data)) {
            if (isset($result->meta['quality'])) {
                imagejpeg($result->data, NULL, $result->meta['quality']);
            } else {
                imagejpeg($result->data);
            }
            imagedestroy($result->data);
        } else {
            echo $result->data;
        }
    }

    protected function processGIF(Result $result) {
        imagegif($result->data);
        imagedestroy($result->data);
    }

    protected function processPNG(Result $result) {
        if (isset($result->meta['quality'])) {
            imagepng($result->data, NULL, $result->meta['quality']);
        } else {
            imagepng($result->data);
        }
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

    public function processINI(Result $result) {
        $array = ["body" => $result->data];

        echo $this->_parseIni($array);
    }

    protected function _parseIni($data, $i = 0) {
        $str = "";
        foreach ($data as $k => $v){
            if ($v === FALSE) {
                $v = "false";
            } elseif ($v === TRUE) {
                $v = "true";
            }

            if (is_array($v)){
                $str .= /*str_repeat(" ",$i*2).*/"[$k]".PHP_EOL;
                $str .= $this->_parseIni($v, $i+1);
            }else {
                $str .= /*str_repeat(" ",$i*2).*/"$k=$v".PHP_EOL;
            }
        }

        return $str;
    }

    public function processXML(Result $result) {
        $xml = '<?xml version="1.0" encoding="utf-8"?>'."\n";
        $xml .= self::_array2xml(["body" => $result->data]);

        echo $xml;
    }

    private function _array2xml($array, $level = 0) {
        $xml = '';
        foreach($array as $key=>$val) {
            is_numeric($key) && $key="item id=\"$key\"";
            if($level > 0){
                $xml.= "\t";
            }

            $xml.="<$key>";
            if($level == 0)	$xml.="\n";

            if($val === true){
                $val = '1';
            }elseif($val === false){
                $val = '0';
            }

            $xml .= is_array($val) ? $this->_array2xml($val, ++$level) : $val;
            list($key,) = explode(' ',$key);
            $xml .= "</$key>\n";
        }

        return $xml;
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