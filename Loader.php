<?php

namespace Akari;

include __DIR__ . "/consts.php";

use Akari\exception\LoaderClassNotExists;

class Loader {

    protected static $nsPaths = [
        'Akari' => AKARI_PATH
    ];

    protected static $loaded = [];

    public static function register(string $ns, string $dir) {
        self::$nsPaths[$ns] = $dir;
    }

    public static function getDir(string $ns) {
        return self::$nsPaths[$ns];
    }

    public static function __loaderFn(string $class) {
        $nsPaths = explode(NAMESPACE_SEPARATOR, $class);

        $clsPath = NULL;
        if ( isset(self::$nsPaths[$nsPaths[0]]) ) {
            $basePath = self::$nsPaths[ array_shift($nsPaths)];
            $clsPath = $basePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $nsPaths) . ".php";
        }

        if ($clsPath && file_exists($clsPath)) {
            if (!isset(self::$loaded[$clsPath])) {
                self::$loaded[$clsPath] = TRUE;
                require $clsPath;
            }
        }
    }

}

spl_autoload_register(['Akari\Loader', '__loaderFn']);

// load core functions
include __DIR__ . '/Core.php';
include __DIR__ . '/functions.php';
include __DIR__ . '/defaultBoot.php';