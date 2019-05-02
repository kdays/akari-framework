<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-26
 * Time: 19:14
 */

namespace Akari\system\container;

use Akari\system\ioc\Injectable;
use Akari\system\util\helper\AppValueTrait;

/**
 * Class BaseTask
 * @package Haru\system\container
 *
 * @property \Akari\system\console\Output $output
 * @property \Akari\system\console\Input $input
 */
abstract class BaseTask extends Injectable {

    use AppValueTrait;


    protected function msg(string $message, int $newLines = 1) {
        return $this->output->write($message, $newLines);
    }

    protected function ask(?string $message, ?array $options = []) {
        if (!empty($options)) {
            if (empty($message)) $message = ' - ';
            $message .= " [" . implode("/", $options) . "]";
            $message = "<question>" . $message . "</question>";
        }

        if (!empty($message)) {
            $this->msg($message);
        }

        if (!empty($options)) {
            while (TRUE) {
                $result = $this->input->getInput();
                if (!in_array($result, $options)) {
                    $this->msg("<info>请在规定范围内选择</info>");
                } else {
                    return $result;
                }
            }
        }

        return $this->input->getInput();
    }



}
