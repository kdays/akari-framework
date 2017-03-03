<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/31
 * Time: 10:42
 */

namespace Akari\action;

use Akari\system\ioc\Injectable;
use Akari\utility\helper\Logging;
use Akari\system\console as console;
use Akari\utility\helper\CacheHelper;
use Akari\utility\helper\ValueHelper;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\ExceptionSetter;

class BaseTask extends Injectable{

    use Logging, ValueHelper, ExceptionSetter, ResultHelper, CacheHelper;

    /** @var  console\Input */
    protected $input;

    /** @var  console\Output */
    protected $output;

    public function __construct() {
        $this->input = new console\Input();
        $this->output = new console\Output();
    }

    protected function message($text, $style = NULL) {
        if (is_array($text)) {
            $r = '';
            foreach ($text as $k => $p) {
                $r .= " [Array] " . $k . " -> " . $p;
            }

            $text = $r;
        }

        if ($style !== NULL) {
            $text = "<" . $style . ">" . $text . "</" . $style . ">";
        }

        return $this->output->write($text);
    }

}
