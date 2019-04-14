<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-02-28
 * Time: 21:54
 */

namespace Akari\system\view\engine;


use Akari\Core;
use Akari\system\ioc\Injectable;
use Akari\system\view\ViewFunctions;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

class TwigViewEngine extends BaseViewEngine {

    protected $loader;

    protected function getTwigLoader() {
        if ($this->loader === NULL) {
            $loader = new FilesystemLoader( Core::$baseDir );

            $options = [
                'auto_reload' => TRUE
            ];

            $cacheDir = $this->options['cacheDir'] ?? FALSE;
            if ($cacheDir) {
                $options['cache'] = $cacheDir;
            }

            $twig = new Environment($loader, $options);
            $this->registerEngineFunctions($twig);

            $this->loader = $twig;
        }

        return $this->loader;
    }

    public function parse($tplPath, array $data, $type, $onlyCompile = FALSE) {
        $that = $this;
        $getView = function($path, $data) use($that, $type) {
            ob_start();
            $baseDir = Core::$baseDir;
            $pathName = str_replace([$baseDir], '', $path);

            echo $that->getTwigLoader()->render($pathName, $data);

            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        };

        $content = $getView($tplPath, $data);

        return $content;
    }

    public function getResult($layoutResult, $screenResult) {
        if (empty($screenResult)) {
            return $layoutResult;
        }

        $screenCmd = $this->options['screenCmd'] ?? '#screen#';
        return str_replace($screenCmd, $screenResult, $layoutResult);
    }

    public function registerEngineFunctions(Environment $twig) {
        /** @var Injectable $that */
        $that = $this;
        $options = ['is_safe' => ['html']];

        ViewFunctions::registerFunction('L', function($lang, array $parameters = []) use($that) {
            return ViewFunctions::lang($lang, $parameters);
        });

        $methodMap = ViewFunctions::getRegisteredFunctions(TRUE);
        foreach ($methodMap as $func) {
            $func = new TwigFunction($func, function() use($func) {
                return call_user_func_array([ViewFunctions::class, $func], func_get_args());
            }, $options);
            $twig->addFunction($func);
        }

        // 语言转换
        $i18nFilter = new TwigFilter('t', function($string) {
            return ViewFunctions::lang($string);
        }, $options);
        $twig->addFilter($i18nFilter);
    }

}
