<?php
namespace Akari\system\console;

class ConsoleInput {
    protected $input;
    protected $_canReadline;

    public function __construct($handle = 'php://stdin') {
        $this->_canReadline = extension_loaded('readline') && $handle === 'php://stdin' ? true : false;
        $this->input = fopen($handle, 'r');
    }

    public function read() {
        if ($this->_canReadline) {
            $line = readline('');
            if (!empty($line)) {
                readline_add_history($line);
            }
            return $line;
        }
        return fgets($this->input);
    }

    public function dataAvailable($timeout = 0) {
        $readFds = array($this->input);
        $readyFds = stream_select($readFds, $writeFds, $errorFds, $timeout);
        return ($readyFds > 0);
    }
}