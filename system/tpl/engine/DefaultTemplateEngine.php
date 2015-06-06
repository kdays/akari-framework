<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/6
 * Time: 上午12:49
 */
namespace Akari\system\tpl\engine;
use Akari\system\tpl\TemplateCommandInvalid;

/**
 * Class DefaultTemplateEngine
 * @package Akari\system\tpl\engine
 *
 */
class DefaultTemplateEngine extends BaseTemplateEngine{

    public function parse($tplPath, array $data, $type, $onlyCompile = false) {
        $this->engineArgs = $data;
        $cachePath = $this->getCachePath($tplPath);

        if (filemtime($tplPath) > filemtime($cachePath) || !file_exists($cachePath)) {
            $template = file_get_contents($tplPath);

            $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
            $var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";

            $that = $this;

            $template = preg_replace_callback('/<!--#(.*?)-->/iu', function($matches) use($that) {
                return $that->parseCommand($matches[1]);
            }, $template);

            $template = preg_replace_callback('/\{'.$const_regexp.'\}/s', function($matches) {
                return '<?='.$matches[1].'?>';
            }, $template);

            $template = preg_replace_callback("/$var_regexp/s", function($matches) use($that) {
                return $that->addQuote('<?='.$matches[1].'?>');
            }, $template);


            $template = preg_replace_callback('/\<\?\=\<\?\='.$var_regexp.'\?\>\?\>/s', function($matches) use($that) {
                return $that->addQuote('<?='.$matches[1].'?>');
            }, $template);

            $template = str_replace("_#_", "\$", $template);
            $template .= $this->getSecurityHash($tplPath, $template);

            $tempHeader = <<<'TAG'
<?php
use Akari\Context;
?>
TAG;

            $template = $tempHeader. $template;
            writeover($cachePath, $template);
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

        return str_replace("<!--@screen-->", $screenResult, $layoutResult);
    }

    private function parseCommand($command) {
        $command = explode(" ", str_replace("$", "_#_", $command));

        $cmd = array_shift($command);
        $afterCommand = implode(' ', $command);

        // 有几个要直接写入 不需要TemplateModel判断的条件语句
        switch ($cmd) {
            case "var":
                return '<?='. $afterCommand. '?>';

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
                return "<?php if(is_array($command[0])||is_object($command[0]))foreach($afterCommand): ?>";

            case "endloop":
                return "<?php endforeach; ?>";

            case "for":
                return "<?php for($afterCommand): ?>";

            case "endfor":
                return "<?php endfor; ?>";
        }

        $cmdAction = '\Akari\system\tpl\TemplateCommand::'. $cmd. "Action";
        if (!method_exists('\Akari\system\tpl\TemplateCommand', $cmd. "Action")) {
            throw new TemplateCommandInvalid($cmd, $afterCommand);
        }

        return call_user_func_array($cmdAction, array_merge([$this], $command));
    }

}