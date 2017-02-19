<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:36.
 */

namespace Akari\system\http;

use Akari\utility\helper\Logging;

class Session
{
    use Logging;

    public static function init()
    {
        session_start();
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function destroy()
    {
        session_destroy();
    }

    public static function has($key)
    {
        return array_key_exists($key, $_SESSION);
    }

    public static function remove($key)
    {
        unset($_SESSION[$key]);
    }

    public static function get($key, $defaultValue = null)
    {
        if ($key == null) {
            return $_SESSION;
        }

        if (isset($_SESSION[$key])) {
            return $_SESSION[$key];
        }

        return $defaultValue;
    }
}
