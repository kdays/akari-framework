<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-25
 * Time: 14:17
 */
namespace Akari;

include(__DIR__ . "/consts.php");

use Akari\exception\LoaderClassNotExists;
use Akari\system\exception\LoaderException;

class Loader {

    protected static $nsPaths = [
        'Akari' => AKARI_PATH
    ];

    protected static $classes = [];

    public static function register(string $ns, string $dir) {
        self::$nsPaths[$ns] = $dir;
    }

    public static function getDir(string $ns) {
        return self::$nsPaths[$ns];
    }

    public static function __loaderFn(string $class) {
        $nsPaths = explode(NAMESPACE_SEPARATOR, $class);

        if ( isset(self::$nsPaths[$nsPaths[0]]) ) {
            $basePath = self::$nsPaths[ array_shift($nsPaths)];
            $clsPath = $basePath . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $nsPaths) . ".php";
        }


        if(isset(self::$classes[$clsPath]))	return ;
        if($clsPath && file_exists($clsPath)) {
            self::$classes[$clsPath] = TRUE;
            require_once $clsPath;
        } else {
            throw new LoaderClassNotExists($class);
        }
    }

}

spl_autoload_register(['Akari\Loader', '__loaderFn']);

// load core functions
include(__DIR__ . '/Core.php');
include(__DIR__ . '/functions.php');
