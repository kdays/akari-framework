<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/1/1
 * Time: 15:54
 */

namespace Akari\system\exception;

use Akari\Context;
use Akari\system\http\Request;
use Akari\system\ioc\DIHelper;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\ValueHelper;

abstract Class BaseExceptionHandler {

    use Logging, ResultHelper, ValueHelper, DIHelper;
    
    /** @var  Request $request */
    protected $request;
    
    public function __construct() {
        $this->request = $this->_getDI()->getShared("request");
    }

    abstract public function handleException(\Exception $ex);
    
    protected function crash($file, $line, $trace) {
        $count = count($trace);
        $padLen = strlen($count);
        foreach ($trace as $key => $call) {
            if (!isset($call['file']) || $call['file'] == '') {
                $call['file'] = 'Internal Location';
                $call['line'] = 'N/A';
            }else{
                $call['file'] = str_replace(Context::$appBasePath, '', $call['file']);
            }
            $traceLine = '#' . str_pad(($count - $key), $padLen, "0", STR_PAD_LEFT) . ' ' . self::getCallLine(
                    $call);

            $trace[$key] = $traceLine;
        }

        $fileLines = array();
        if (is_file($file)) {
            $currentLine = $line - 1;

            $fileLines = explode("\n", file_get_contents($file, null, null, 0, 10000000));
            $topLine = $currentLine - 5;
            $fileLines = array_slice($fileLines, $topLine > 0 ? $topLine : 0, 10, true);

            if (($count = count($fileLines)) > 0) {
                $padLen = strlen($count);
                foreach ($fileLines as $line => $fileLine){
                    $fileLine = " <b>" .str_pad($line + 1, $padLen, "0", STR_PAD_LEFT) . "</b> " . htmlspecialchars(str_replace("\t",
                            "    ", rtrim($fileLine)), NULL, 'UTF-8');
                    $fileLines[$line] = $fileLine;
                }
            }
        }

        return array($fileLines, $trace);
    }

    protected function getCallLine(array $call) {
        $call_signature = "";
        if (isset($call['file'])) $call_signature .= $call['file'] . " ";
        if (isset($call['line'])) $call_signature .= ":" . $call['line'] . " ";
        if (isset($call['function'])) {
            $call_signature .= '<span class="func">';
            if(isset($call['class'])) $call_signature .= "$call[class]->";
            $call_signature .= $call['function']."(";
            if (isset($call['args'])) {
                foreach ($call['args'] as $arg) {
                    if (is_string($arg))
                        $arg = '"' . (strlen($arg) <= 64 ? $arg : substr($arg, 0, 64) . "…") . '"';
                    else if (is_object($arg))
                        $arg = "[Instance of '" . get_class($arg) . "']";
                    else if ($arg === true)
                        $arg = "true";
                    else if ($arg === false)
                        $arg = "false";
                    else if ($arg === null)
                        $arg = "null";
                    else if (is_array($arg))
                        $arg = '[Array]';
                    else
                        $arg = strval($arg);
                    $call_signature .= $arg . ',';
                }
                $call_signature = trim($call_signature, ',') . ")</span>";
            }
        }
        return $call_signature;
    }

}