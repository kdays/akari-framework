<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-26
 * Time: 19:16
 */

namespace Akari\system\console;

class Output {

    const PLAIN = 1;
    const COLOR = 2;
    const LF = PHP_EOL;

    protected static $fntColor = [
        'black' => 30,
        'red' => 31,
        'green' => 32,
        'yellow' => 33,
        'blue' => 34,
        'magenta' => 35,
        'cyan' => 36,
        'white' => 37
    ];

    protected static $bgColor = [
        'black' => 40,
        'red' => 41,
        'green' => 42,
        'yellow' => 43,
        'blue' => 44,
        'magenta' => 45,
        'cyan' => 46,
        'white' => 47
    ];

    protected static $_options = [
        'bold' => 1,
        'underline' => 4,
        'blink' => 5,
        'reverse' => 7,
    ];

    protected static $_styles = [
        'fatal' => ['text' => 'red', 'underline' => TRUE],
        'alert' => ['text' => 'red', 'underline' => TRUE],
        'critical' => ['text' => 'red', 'underline' => TRUE],
        'error' => ['text' => 'red', 'underline' => TRUE],
        'warning' => ['text' => 'yellow'],
        'info' => ['text' => 'cyan'],
        'debug' => ['text' => 'white'],
        'success' => ['text' => 'green'],
        'comment' => ['text' => 'blue'],
        'question' => ['text' => 'magenta'],
        'notice' => ['text' => 'cyan']
    ];

    protected $output;
    protected $outputAs = self::COLOR;

    public function __construct($stream = 'php://stdout') {
        $this->output = fopen($stream, 'w');
    }

    public function write($message, $newlines = 1) {
        if (is_array($message)) {
            $message = implode(static::LF, $message);
        }

        return $this->_write($this->styleText($message . str_repeat(static::LF, $newlines)));
    }

    public function styleText($text) {
        if ($this->outputAs == static::PLAIN) {
            $tags = implode('|', array_keys(static::$_styles));

            return preg_replace('#</?(?:' . $tags . ')>#', '', $text);
        }

        return preg_replace_callback(
            '/<(?P<tag>[a-z0-9-_]+)>(?P<text>.*?)<\/(\1)>/ims', [$this, '_replaceTags'], $text
        );
    }

    protected function _replaceTags($matches) {
        $style = $this->styles($matches['tag']);
        if (empty($style)) {
            return '<' . $matches['tag'] . '>' . $matches['text'] . '</' . $matches['tag'] . '>';
        }

        $styleInfo = [];
        if (!empty($style['text']) && isset(static::$fntColor[$style['text']])) {
            $styleInfo[] = static::$fntColor[$style['text']];
        }
        if (!empty($style['background']) && isset(static::$bgColor[$style['background']])) {
            $styleInfo[] = static::$bgColor[$style['background']];
        }
        unset($style['text'], $style['background']);
        foreach ($style as $option => $value) {
            if ($value) {
                $styleInfo[] = static::$_options[$option];
            }
        }

        return "\033[" . implode(';', $styleInfo) . 'm' . $matches['text'] . "\033[0m";
    }

    public function styles($style = NULL, $definition = NULL) {
        if ($style === NULL && $definition === NULL) {
            return static::$_styles;
        }
        if (is_string($style) && $definition === NULL) {
            return isset(static::$_styles[$style]) ? static::$_styles[$style] : NULL;
        }
        if ($definition === FALSE) {
            unset(static::$_styles[$style]);

            return TRUE;
        }
        static::$_styles[$style] = $definition;

        return TRUE;
    }

    protected function _write($message) {
        return fwrite($this->output, $message);
    }

    public function __destruct() {
        if (is_resource($this->output)) {
            fclose($this->output);
        }
    }

}
