<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午12:49
 */

namespace Akari\system\tpl\engine;

use Akari\system\tpl\TemplateCommandInvalid;

class MinatoTemplateEngine extends BaseTemplateEngine{

    public function parse($tplPath, array $data, $type, $onlyCompile = FALSE) {
        $this->engineArgs = $data;
        $cachePath = $this->getCachePath($tplPath);

        $isAlwaysCompile = $this->getOption('alwaysCompile', FALSE);

        $beginCmdMark = preg_quote($this->getOption('beginCmdMark', '<!--#'));
        $endCmdMark = preg_quote($this->getOption('endCmdMark', '-->'));
        $beginLangMark = preg_quote($this->getOption('beginI18nMark', '{%'));
        $endLangMark = preg_quote($this->getOption('endI18nMark', "}"));
        $beginUtilMark = preg_quote($this->getOption('beginUtilMark', '{{'));
        $endUtilMark = preg_quote($this->getOption('endUtilMark', '}}'));

        if (file_exists($cachePath)) {
            $isNeedCreate = filemtime($tplPath) > filemtime($cachePath);   
        } else {
            $isNeedCreate = TRUE;
        }

        if ($isNeedCreate || $isAlwaysCompile) {
            $template = file_get_contents($tplPath);

            $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
            $var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";

            $that = $this;

            $template = preg_replace_callback('/' . $beginCmdMark . '(.*?)' . $endCmdMark . '/iu', function ($matches) use ($that) {
                return $that->parseCommand($matches[1]);
            }, $template);

            $template = preg_replace_callback('/\{' . $const_regexp . '\}/s', function ($matches) {
                return '<?=' . $matches[1] . '?>';
            }, $template);

            $template = preg_replace_callback('/' . $beginUtilMark . '(.*?)' . $endUtilMark . '/iu', function ($matches) use ($that) {
                $cmd = trim(str_replace("$", "_#_", $matches[1]));

                $cmdPos = strpos($cmd, "(");
                $cmdName = substr($cmd, 0, $cmdPos);
                $cmdParameters = substr($cmd, $cmdPos + 1, -1);

                if (in_string($cmdName, ".")) {
                    $cmdFunc = explode(".", $cmdName);

                    $viewCmd = 'ViewUtil::' . $cmdFunc[0] . "('" . $cmdFunc[1] . "', " . $cmdParameters . ")";
                    if (empty($cmdParameters)) {
                        $viewCmd = 'ViewUtil::' . $cmdFunc[0] . "('" . $cmdFunc[1] . "')";
                    }
                } else {
                    $viewCmd = 'ViewUtil::' . $cmdName . "(" . $cmdParameters . ")";
                }

                return '<?=' . $viewCmd . "?>";
            }, $template);

            $template = preg_replace_callback('/' . $beginLangMark . '(.*?)' . $endLangMark . '/iu', function ($matches) {
                $matches[1] = str_replace("$", "_#_", $matches[1]);

                return "<?=L(\"$matches[1]\")?>";
            }, $template);

            $template = preg_replace_callback("/$var_regexp/s", function ($matches) use ($that) {
                return $that->addQuote('<?=' . $matches[1] . '?>');
            }, $template);

            $template = preg_replace_callback('/\<\?\=\<\?\=' . $var_regexp . '\?\>\?\>/s', function ($matches) use ($that) {
                return $that->addQuote('<?=' . $matches[1] . '?>');
            }, $template);

            $template = str_replace("_#_", "\$", $template);

            $tempHeader = <<<'TAG'
<?php
use Akari\Context;
use Akari\system\tpl\TemplateUtil AS ViewUtil;
?>
TAG;

            $template = $tempHeader . $template;
            file_put_contents($cachePath, $template);
        }

        return $onlyCompile ? $cachePath : self::_getView($cachePath, $data);
    }

    public function addQuote($var) {
        return str_replace("\\\"", "\"", preg_replace('/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s', "['\\1']", $var));
    }

    public function getResult($layoutResult, $screenResult) {
        if (empty($screenResult)) {
            return $layoutResult;
        }

        if (empty($layoutResult) && !empty($screenResult)) {
            return $screenResult;
        }

        $screenCmd = $this->getOption('screenCmdMark', '<!--@screen-->');

        return str_replace($screenCmd, $screenResult, $layoutResult);
    }

    /**
     * @param $command
     * @return mixed|string
     * @throws TemplateCommandInvalid
     */
    private function parseCommand($command) {
        $command = explode(" ", trim(str_replace("$", "_#_", $command)));

        $cmd = array_shift($command);
        $afterCommand = implode(' ', $command);

        // 有几个要直接写入 不需要TemplateModel判断的条件语句
        switch ($cmd) {
            case "set":
                return "<?php " . $afterCommand . "?>";

            case "var":
                return '<?=' . $afterCommand . '?>';

            case "if":
                return "<?php if($afterCommand): ?>";

            case "elif":
            case "elseif":
                return "<?php elseif($afterCommand): ?>";

            case "else":
                return "<?php else: ?>";

            case "endif":
                return "<?php endif; ?>";

            case "loop":
                return "<?php foreach($afterCommand): ?>";

            case "endloop":
            case "loopend":
                return "<?php endforeach; ?>";

            case "for":
                return "<?php for($afterCommand): ?>";

            case "endfor":
                return "<?php endfor; ?>";
        }

        throw new TemplateCommandInvalid($cmd, $afterCommand);
    }

}
