<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:09
 */

namespace Akari\system\console;

use Akari\system\ioc\Injectable;

Class Input extends Injectable {

    protected $input;
    protected $parameters = [];
    
    public function __construct($handle = 'php://stdin') {
        $this->input = fopen($handle, 'r');
        
        if ($this->dispatcher) {
            $this->parameters = $this->dispatcher->getParameters();
        }
    }
    
    public function __destruct() {
        // TODO: Implement __destruct() method.
        if ($this->input) {
            fclose($this->input);
        }
    }

    public function getInput() {
        return trim(fgets($this->input));
    }
    
    public function hasParameter($key) {
        return !!array_key_exists($key, $this->parameters);
    }

    public function getParameter($key, $defaultValue = NULL) {
        if (empty($key)) {
            return $this->parameters;
        }

        return isset($this->parameters[$key]) ? $this->parameters[$key] : $defaultValue;
    }


}