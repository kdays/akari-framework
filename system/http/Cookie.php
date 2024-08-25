<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-27
 * Time: 16:16
 */

namespace Akari\system\http;

use Akari\system\ioc\Injectable;
use Akari\system\util\helper\AppValueTrait;

class Cookie extends Injectable {

    use AppValueTrait;

    protected $_values = [];

    public function exists(string $name, $prefix = TRUE) {
        if ($prefix) {
            $prefix = is_string($prefix) ? $prefix : $this->_getConfigValue("cookiePrefix", '');
        } else {
            $prefix = '';
        }

        return array_key_exists($prefix . $name, $_COOKIE);
    }

    public function set(string $name, string $value, ?int $time = NULL, array $options = []) {
        if (is_numeric($time)) {
            $time += TIMESTAMP;
        } else {
            $time = (empty($time) || $time == 'now') ? 0 : strtotime($time);
        }

        $path = $options['path'] ?? $this->_getConfigValue("cookiePath", '/');
        $domain = $options['domain'] ?? $this->_getConfigValue("cookieDomain", '');
        $prefix = $options['prefix'] ?? $this->_getConfigValue("cookiePrefix", '');

        $name = $prefix . $name;

        $cookieOptions = [
            'path' => $path,
            'domain' => $domain,
            'expires' => $time,
            'httponly' => $options['http_only'] ?? FALSE,
        ];

        if (isset($options['samesite'])) $cookieOptions['samesite'] = $options['samesite'];
        if (isset($options['secure'])) $cookieOptions['secure'] = $options['secure'];

        if ($value === FALSE) {
            $cookieOptions['expires'] = TIMESTAMP - 3600;

            setcookie($name, '', $cookieOptions);
            //setcookie($name, '', TIMESTAMP - 3600, $path, $domain, false, $options['http_only'] ?? FALSE);
            unset($this->_values[$name]);
        } else {
            setcookie($name, $value, $cookieOptions);
            $this->_values[$name] = $value;
        }
    }

    public function get(string $name, $prefix = TRUE, $defaultValue = NULL) {
        if ($prefix) {
            $prefix = is_string($prefix) ? $prefix : $this->_getConfigValue("cookiePrefix", '');
        } else {
            $prefix = '';
        }

        $name = $prefix . $name;

        if (isset($this->_values[$name])) {
            return $this->_values[$name];
        }

        return array_key_exists($name, $_COOKIE) ? $_COOKIE[$name] : $defaultValue;
    }

    public function remove(string $key, $prefix = TRUE) {
        if ($prefix) {
            $prefix = is_string($prefix) ? $prefix : $this->_getConfigValue("cookiePrefix", '');
        } else {
            $prefix = '';
        }

        return $this->set($key, FALSE, NULL, ['prefix' => $prefix]);
    }

    public function reset() {
        $this->_values = [];
    }

}
