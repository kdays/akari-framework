<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-08
 * Time: 14:09
 */

namespace Akari\system\util;

use Akari\Core;
use Akari\system\ioc\DI;
use Akari\system\event\Event;
use Akari\system\http\Response;
use Akari\system\result\Result;
use Akari\exception\AkariException;
use Akari\system\router\Dispatcher;
use Akari\system\view\DefaultExceptionProcessor;
use Akari\system\security\BaseExceptionProcessor;

class ExceptionUtil {

    public static function dispatchException(\Throwable $ex) {
        static $fired = FALSE;

        if (CLI_MODE) {
            echo "\n-------\n\033[31;49;1m" . $ex->getMessage() . "\n" . get_class($ex) . "\033[39;49;0m";

            echo "\n\n" . $ex->getTraceAsString() . "\n";
            die;
        }

        $di = DI::getDefault();

        // 首先从基础字段中拿到Result情况 如果有的话
        if (ob_get_level() != 0) ob_end_clean();

        if ($di->hasShared('exceptionProcessor')) {
            /** @var BaseExceptionProcessor $exProcessor */
            $exProcessor = $di->getShared('exceptionProcessor');
        } else {
            $exProcessor = new DefaultExceptionProcessor();
        }

        $result = $exProcessor->process($ex);
        if ($result instanceof Result) {
            $di->getShared('processor')->process($result);
        } else {
            throw new AkariException(get_class($exProcessor) . " not returning Result");
        }

        if (!$fired) {
            $fired = TRUE;
            Event::fire(Dispatcher::EVENT_APP_END, NULL);
        }

        /** @var Response $response */
        $response = $di->getShared('response');
        $response->send();
    }

    public static function getCrashDebugInfo(string $file, int $line, array $trace) {
        $count = count($trace);
        $padLen = strlen($count);
        foreach ($trace as $key => $call) {
            if (!isset($call['file']) || $call['file'] == '') {
                $call['file'] = 'Internal Location';
                $call['line'] = 'N/A';
            }else{
                $call['file'] = str_replace(Core::$appDir, '', $call['file']);
            }
            $traceLine = '#' . str_pad(($count - $key), $padLen, "0", STR_PAD_LEFT) . ' ' . self::getCallLine(
                    $call);

            $trace[$key] = $traceLine;
        }

        $fileLines = array();
        if (is_file($file)) {
            $currentLine = $line - 1;

            $fileLines = explode("\n", file_get_contents($file, NULL, NULL, 0, 10000000));
            $topLine = $currentLine - 5;
            $fileLines = array_slice($fileLines, $topLine > 0 ? $topLine : 0, 10, TRUE);

            if (($count = count($fileLines)) > 0) {
                $padLen = strlen($count);
                foreach ($fileLines as $line => $fileLine){
                    $fileLine = " <b>" . str_pad($line + 1, $padLen, "0", STR_PAD_LEFT) . "</b> " . htmlspecialchars(str_replace("\t",
                            "    ", rtrim($fileLine)), NULL, 'UTF-8');
                    $fileLines[$line] = $fileLine;
                }
            }
        }

        return [$fileLines, $trace];
    }

    protected static function getCallLine(array $call) {
        $call_signature = "";
        if (isset($call['file'])) $call_signature .= $call['file'] . " ";
        if (isset($call['line'])) $call_signature .= ":" . $call['line'] . " ";
        if (isset($call['function'])) {
            $call_signature .= '<span class="func">';
            if(isset($call['class'])) $call_signature .= "$call[class]->";
            $call_signature .= $call['function'] . "(";
            if (isset($call['args'])) {
                foreach ($call['args'] as $arg) {
                    if (is_string($arg))
                        $arg = '"' . (strlen($arg) <= 64 ? $arg : substr($arg, 0, 64) . "…") . '"';
                    elseif (is_object($arg))
                        $arg = "[Instance of '" . get_class($arg) . "']";
                    elseif ($arg === TRUE)
                        $arg = "true";
                    elseif ($arg === FALSE)
                        $arg = "false";
                    elseif ($arg === NULL)
                        $arg = "null";
                    elseif (is_array($arg))
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
