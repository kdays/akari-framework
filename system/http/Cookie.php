<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:05.
 */

namespace Akari\system\http;

use Akari\Context;
use Akari\system\ioc\DIHelper;
use Akari\system\security\cipher\Cipher;

class Cookie
{
    use DIHelper;

    const FLAG_ENCRYPT = '|ENC';
    const FLAG_ARRAY = '|A';

    private $_directFast = [];

    public function exists($name, $autoPrefix = true)
    {
        $config = Context::$appConfig;
        if (!in_string($name, $config->cookiePrefix) && $autoPrefix) {
            $name = $config->cookiePrefix.$name;
        }

        return (bool) array_key_exists($name, $_COOKIE);
    }

    public function set($name, $value, $expire = null, $useEncrypt = false, $opts = [])
    {
        $config = Context::$appConfig;

        $expire = isset($expire) ? $expire : $config->cookieTime;
        if (is_numeric($expire)) {
            $expire += TIMESTAMP;
        } else {
            $expire = $expire == 'now' ? 0 : strtotime($expire);
        }

        if (is_array($value)) {
            $value = http_build_query($value).self::FLAG_ARRAY;
        }

        if ($useEncrypt) {
            /** @var Cipher $cipher */
            $cipher = $this->_getDI()->getShared('cookieEncrypt');
            $value = $cipher->encrypt($value);
        }

        $path = array_key_exists('path', $opts) ? $opts['path'] : $config->cookiePath;
        $domain = array_key_exists('domain', $opts) ? $opts['domain'] : $config->cookieDomain;

        if ($config->cookiePrefix) {
            $name = $config->cookiePrefix.$name;
        }

        if ($value === false) {
            setcookie($name, '', time() - 3600, $path, $domain);
            unset($this->_directFast[$name]);
        } else {
            setcookie($name, $value, $expire, $path, $domain);
            if (array_key_exists('direct', $opts) && $opts['direct']) {
                $this->_directFast[$name] = $value;
            }
        }
    }

    public function get($name, $autoPrefix = true)
    {
        $config = Context::$appConfig;

        if (!in_string($name, $config->cookiePrefix) && $autoPrefix) {
            $name = $config->cookiePrefix.$name;
        }

        if (isset($this->_directFast[$name])) {
            return $this->_directFast[$name];
        }

        if (!array_key_exists($name, $_COOKIE)) {
            return;
        }
        $cookie = $_COOKIE[$name];

        /** @var Cipher $encrypt */
        $encrypt = $this->_getDI()->getShared('cookieEncrypt');
        $cookie = $encrypt->decrypt($cookie);

        if (substr($cookie, -2, 2) == self::FLAG_ARRAY) {
            $result = [];
            $arrayFlagLen = strlen(self::FLAG_ARRAY) + 1;

            parse_str(substr($cookie, 0, count($cookie) - $arrayFlagLen), $result);

            return $result;
        }

        return $cookie;
    }

    public function remove($key)
    {
        $this->set($key, false);
    }
}
