<?php

namespace Akari\system\util\helper;

trait Instance {

    protected static $instance = NULL;

    /**
     * @param array $options
     * @return static
     */
    public static function instance($options = []) {
        if (is_null(self::$instance)) {
            self::$instance = new self($options);
        }

        return self::$instance;
    }

}
