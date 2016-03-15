<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 09:03
 */

namespace Akari\system\result;

use Akari\NotAllowConsole;
use Akari\system\http\Response;
use Akari\system\ioc\DIHelper;
use Akari\system\tpl\TemplateHelper;
use Akari\system\tpl\ViewHelper;

Class Processor {
    
    use DIHelper;
    
    public function processJPEG(Result $result) {
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

    public function processGIF(Result $result) {
        imagegif($result->data);
        imagedestroy($result->data);
    }

    public function processPNG(Result $result) {
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

    public function processTPL(Result $result) {
        $layoutPath = $result->meta['layout'];
        $screenPath = $result->meta['view'];

        $h = new TemplateHelper();

        if ($screenPath == NULL || $layoutPath == NULL) {
            if ($screenPath !== NULL)   ViewHelper::setScreen($screenPath);
            if ($layoutPath !== NULL)   ViewHelper::setLayout($layoutPath);

            $realScreenPath = ViewHelper::getScreen();
            $h->setScreen($realScreenPath);

            $realLayoutPath = ViewHelper::getLayout();
            if (!empty($realLayoutPath)) {
                $h->setLayout($realLayoutPath);
            }
        }

        if (is_array($result->data)) {
            TemplateHelper::assign($result->data, NULL);
        }

        $screenResult = $h->getResult(NULL);
        echo $screenResult;
    }

    public function processHTML(Result $result) {
        echo $result->data;
    }

    public function processTEXT(Result $result) {
        echo $result->data;
    }

    public function processINI(Result $result) {
        $array = ["body" => $result->data];

        function _array2ini($data, $i) {
            $str = "";
            foreach ($data as $k => $v){
                if ($v === FALSE) {
                    $v = "false";
                } elseif ($v === TRUE) {
                    $v = "true";
                }

                if (is_array($v)){
                    $str .= /*str_repeat(" ",$i*2).*/"[$k]".PHP_EOL;
                    $str .= _array2ini($v, $i+1);
                } else {
                    $str .= /*str_repeat(" ",$i*2).*/"$k=$v".PHP_EOL;
                }
            }

            return $str;
        }

        echo _array2ini($array, 0);
    }


    public function processXML(Result $result) {
        function _array2xml($array, $level = 0) {
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

                $xml .= is_array($val) ? _array2xml($val, ++$level) : $val;
                list($key,) = explode(' ',$key);
                $xml .= "</$key>\n";
            }

            return $xml;
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>'."\n";
        $xml .= _array2xml(["body" => $result->data]);

        echo $xml;
    }


    public function processNONE(Result $result) {

    }

    public function processCUSTOM(Result $result) {
        echo $result->data;
    }

    public function processResult(Result $result) {
        $method = "process".$result->type;
        
        if (CLI_MODE) {
            throw new NotAllowConsole('return result on Processor');
        }
        
        /** @var Response $resp */
        $resp = $this->_getDI()->getShared('response');
        $resp->setContentType($result->contentType);
        
        if (method_exists($this, $method)) {
            $this->$method($result);
            $resp->send();
        } else {
            throw new ResultTypeUnknown($result);
        }
    }

}

Class ResultTypeUnknown extends \Exception {
    
    protected $errResult;
    
    public function __construct($errResult) {
        $this->message = "Result Type Unknown";
        $this->errResult = $errResult;
    }
    
    public function getResult() {
        return $this->errResult;
    }

}