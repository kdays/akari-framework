<?php
namespace Akari\system\console;

Class ConsoleIO {

    const VERBOSE = 2;
    const NORMAL = 1;
    const QUIET = 0;

    protected $_level = ConsoleIo::NORMAL;
    protected $_lastWritten = 0;

    protected $_out;
    protected $_in;
    protected $_err;

    public function __construct(ConsoleOutput $out = null, ConsoleOutput $err = null, ConsoleInput $in = null) {
        $this->_out = $out ? $out : new ConsoleOutput('php://stdout');
        $this->_err = $err ? $err : new ConsoleOutput('php://stderr');
        $this->_in = $in ? $in : new ConsoleInput('php://stdin');
    }

    public function err($message = null, $newlines = 1) {
        $this->_err->write($message, $newlines);
    }

    public function hr($newlines = 0, $width = 79) {
        $this->out(null, $newlines);
        $this->out(str_repeat('-', $width));
        $this->out(null, $newlines);
    }

    public function out($message = null, $newlines = 1, $level = ConsoleIo::NORMAL) {
        if ($level <= $this->_level) {
            $this->_lastWritten = $this->_out->write($message, $newlines);
            return $this->_lastWritten;
        }
        return true;
    }

    public function overwrite($message, $newlines = 1, $size = null) {
        $size = $size ?: $this->_lastWritten;

        // Output backspaces.
        $this->out(str_repeat("\x08", $size), 0);

        $newBytes = $this->out($message, 0);

        // Fill any remaining bytes with spaces.
        $fill = $size - $newBytes;
        if ($fill > 0) {
            $this->out(str_repeat(' ', $fill), 0);
        }
        if ($newlines) {
            $this->out($this->nl($newlines), 0);
        }
    }

    public function nl($multiplier = 1) {
        return str_repeat(ConsoleOutput::LF, $multiplier);
    }

    public function ask($prompt, $default = null) {
        return $this->_getInput($prompt, null, $default);
    }

    public function askChoice($prompt, $options, $default = null) {
        $originalOptions = $options;

        if ($options && is_string($options)) {
            if (strpos($options, ',')) {
                $options = explode(',', $options);
            } elseif (strpos($options, '/')) {
                $options = explode('/', $options);
            } else {
                $options = [$options];
            }
        }

        $printOptions = '(' . implode('/', $options) . ')';
        $options = array_merge(
            array_map('strtolower', $options),
            array_map('strtoupper', $options),
            $options
        );
        $in = '';
        while ($in === '' || !in_array($in, $options)) {
            $in = $this->_getInput($prompt, $printOptions, $default);
        }
        return $in;
    }

    protected function _getInput($prompt, $options, $default) {
        $optionsText = '';
        if (isset($options)) {
            $optionsText = " $options ";
        }

        $defaultText = '';
        if ($default !== null) {
            $defaultText = "[$default] ";
        }
        $this->_out->write('<question>' . $prompt . "</question>$optionsText\n$defaultText> ", 0);
        $result = $this->_in->read();

        $result = trim($result);
        if ($default !== null && ($result === '' || $result === null)) {
            return $default;
        }
        return $result;
    }

}