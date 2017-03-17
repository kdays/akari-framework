<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 09:03
 */

namespace Akari\system\result;

use Akari\system\event\Listener;
use Akari\system\ioc\Injectable;
use Akari\system\exception\AkariException;

class Processor extends Injectable{

    const EVT_RESULT_SENT = "Result.sent";

    public function processJPEG(Result $result) {
        if (is_resource($result->data)) {
            ob_start();

            if (isset($result->meta['quality'])) {
                imagejpeg($result->data, NULL, $result->meta['quality']);
            } else {
                imagejpeg($result->data);
            }
            imagedestroy($result->data);

            return ob_get_clean();
        } 

        return $result->data;
    }

    public function processGIF(Result $result) {
        ob_start();
        imagegif($result->data);
        imagedestroy($result->data);

        return ob_get_clean();
    }

    public function processPNG(Result $result) {
        ob_start();
        if (isset($result->meta['quality'])) {
            imagepng($result->data, NULL, $result->meta['quality']);
        } else {
            imagepng($result->data);
        }
        imagedestroy($result->data);

        return ob_get_clean();
    }

    public function processJSON(Result $result) {
        return json_encode($result->data);
    }

    public function processTPL(Result $result) {
        $layoutPath = $result->meta['layout'];
        $screenPath = $result->meta['view'];

        if ($screenPath !== NULL)   $this->view->setScreen($screenPath);
        if ($layoutPath !== NULL)   $this->view->setLayout($layoutPath);

        if (is_array($result->data)) {
            $this->view->bindVars($result->data);
        }

        return $this->view->getResult(NULL);
    }

    public function processHTML(Result $result) {
        return $result->data;
    }

    public function processTEXT(Result $result) {
        return $result->data;
    }

    public function processINI(Result $result) {
        function _array2ini($data, $i) {
            $str = "";
            $map = [
                FALSE => 'false',
                TRUE => 'true',
                NULL => 'null'
            ];

            foreach ($data as $k => $v){
                if (isset($map[$k])) {
                    $v = $map[$k];
                }

                if (is_array($v)) {
                    $str .= "[$k]" . PHP_EOL;
                    $str .= _array2ini($v, $i + 1);
                } else {
                    $str .= $k . "=" . $v . PHP_EOL;
                }
            }

            return $str;
        }
        
        $body = $result->data;
        if (!is_array($body)) {
            $body = ['body' => $body];
        }

        return _array2ini($body, 0);
    }


    public function processXML(Result $result) {
        function _array2xml($array, $level = 0) {
            $xml = '';
            foreach($array as $key=>$val) {
                is_numeric($key) && $key="item id=\"$key\"";
                if($level > 0){
                    $xml .= "\t";
                }

                $xml .= "<$key>";
                if($level == 0)	$xml .= "\n";

                if($val === TRUE){
                    $val = '1';
                }elseif($val === FALSE){
                    $val = '0';
                }

                $xml .= is_array($val) ? _array2xml($val, ++$level) : $val;
                list($key, ) = explode(' ', $key);
                $xml .= "</$key>\n";
            }

            return $xml;
        }

        $xml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $xml .= _array2xml(["body" => $result->data]);

        return $xml;
    }


    public function processNONE(Result $result) {

    }

    public function processCUSTOM(Result $result) {
        return $result->data;
    }

    public function processResult(Result $result) {
        $method = "process" . $result->type;

        $this->response->setContentType($result->contentType);

        if (method_exists($this, $method)) {
            $this->response->setContent($this->$method($result));
            Listener::fire(self::EVT_RESULT_SENT, $this);
        } else {
            throw new AkariException("Unknown Result Type:" . gettype($result));
        }
    }

}
