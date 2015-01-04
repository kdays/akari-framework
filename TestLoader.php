<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 15:57
 */

namespace Akari;

Class TestLoader {

    private static function getNSRootByFilename($ns, $file) {
        $fileDir = dirname($file);

        $nsStack = explode('\\', $ns);
        $pathStack = explode(DIRECTORY_SEPARATOR, $fileDir);

        while (array_pop($nsStack) === ($dir = array_pop($pathStack))) {
            //just an empty while loop to make $dir as app root namespace
        }

        $appDir = implode(DIRECTORY_SEPARATOR, array_merge($pathStack, [$dir]));

        return $appDir;
    }

    public static function initForTest($namespace, $configCls) {
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'akari.php';
        $arrayNS = explode('\\', $namespace);
        $appNS = array_shift($arrayNS);
        $trace = debug_backtrace();
        $appDir = array_shift($trace)['file'];

        Context::$testing = TRUE;
        $appBasePath = self::getNSRootByFilename($namespace, $appDir);
        akari::getInstance()->initApp($appBasePath, $appNS, $configCls);
    }

}