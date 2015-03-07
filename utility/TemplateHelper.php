<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 15:17
 */

namespace Akari\utility;

use Akari\config\ConfigItem;
use Akari\Context;

Class TemplateHelper {

    public $cacheDirPath;
    public $layoutPath;

    protected static $h;

    public static function getInstance() {
        if (!isset(self::$h)) {
            self::$h = new self();
        }

        return self::$h;
    }

    public function __construct() {
        $this->cacheDirPath = Context::$appConfig->templateCacheDir;
    }

    public function load($tplName, $layoutName = NULL) {
        $config = Context::$appConfig;
        $suffix = $config->templateSuffix ? $config->templateSuffix : ".htm";

        $baseTemplateDirPath = implode(DIRECTORY_SEPARATOR, [
            Context::$appEntryPath, "template", ""
        ]);

        if ($configBaseTemplatePath = C(ConfigItem::BASE_TPL_DIR)) {
            $baseTemplateDirPath = Context::$appEntryPath. DIRECTORY_SEPARATOR. $configBaseTemplatePath;
        }

        $tplName = str_replace('//', '/', $tplName);
        if ($layoutName != NULL)    $layoutName = str_replace('//', '/', $layoutName);

        if (substr($tplName, 0, 1) == '/') $tplName = substr($tplName, 1);
        if (substr($layoutName, 0, 1) == '/')  $layoutName = substr($layoutName, 1);

        if (!file_exists( $templatePath = $baseTemplateDirPath. "view". DIRECTORY_SEPARATOR. $tplName. $suffix )) {
            throw new TemplateNotFound($tplName);
        }

        // 创建模板缓存
        $assignData = $this->data;
        $screenPath = $this->parseTemplate($templatePath);
        $view = function($path, $assignData) {
            ob_start();
            @extract($assignData, EXTR_PREFIX_SAME, 'a_');
            include($path);
            $content = ob_get_contents();
            ob_end_clean();

            return $content;
        };

        if ($layoutName == NULL) {
            return $view($screenPath, $assignData);
        }

        $realLayoutPath = $baseTemplateDirPath. "layout". DIRECTORY_SEPARATOR. $layoutName. $suffix;
        if (!file_exists($realLayoutPath)) {
            throw new TemplateNotFound($layoutName);
        }
        $layoutPath = $this->parseTemplate($realLayoutPath);

        TemplateCommand::$screen = $view($screenPath, $assignData);
        return $view($layoutPath, array_merge([], $assignData));
    }

    /**
     * @param $templatePath
     * @return mixed
     */
    public function parseTemplate($templatePath) {
        $tplName = str_replace(
            Context::$appEntryPath. DIRECTORY_SEPARATOR. "template". DIRECTORY_SEPARATOR,
            '',
            $templatePath
        );
        $tplName = str_replace(['.', '/'], '_', $tplName);
        $tplPath = Context::$appBasePath. $this->cacheDirPath. DIRECTORY_SEPARATOR. $tplName. ".php";

        if (!file_exists($tplPath) || filemtime($tplPath) < filemtime($templatePath)) {
            $content = <<<'TAG'
<?php
    !defined('AKARI_PATH') && exit;

    use Akari\Context;
    use Akari\utility\DataHelper;
    use Akari\utility\TemplateHelper;
    use Akari\utility\TemplateCommand;
?>
TAG;

            $content .= $this->parseTemplateText(file_get_contents($templatePath), $templatePath);
            $content .= "<!--". $tplName. "@". md5($content). "-->";

            file_put_contents($tplPath, $content);
        }

        return $tplPath;
    }

    public function parseTemplateText($template, $path = NULL) {
        $const_regexp = "([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)";
        $var_regexp = "((\\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*(\-\>)?[a-zA-Z0-9_\x7f-\xff]*)(\[[a-zA-Z0-9_\-\.\"\'\[\]\$\x7f-\xff]+\])*)";

        $template = preg_replace_callback('/\{\%(.*?)\}/iu', function($matches) {
            return TemplateHelper::parseTemplateLanguage($matches[1]);
        }, $template);

        $template = preg_replace_callback('/<!--#(.*?)-->/iu', function($matches) use($path) {
            return TemplateHelper::parseTemplateCommand($matches[1], $path);
        }, $template);

        $template = preg_replace_callback('/\{'.$const_regexp.'\}/s', function($matches) {
            return '<?='.$matches[1].'?>';
        }, $template);

        $template = preg_replace_callback("/$var_regexp/s", function($matches) {
            return TemplateHelper::addQuote('<?='.$matches[1].'?>');
        }, $template);


        $template = preg_replace_callback('/\<\?\=\<\?\='.$var_regexp.'\?\>\?\>/s', function($matches) {
            return TemplateHelper::addQuote('<?='.$matches[1].'?>');
        }, $template);

        return str_replace("_#_", "\$", $template);
    }

    public static function addQuote($var) {
        return str_replace("\\\"", "\"", preg_replace('/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s', "['\\1']", $var));
    }

    public static function parseTemplateLanguage($str) {
        $command = explode(" ", $str);
        $langid = array_shift($command);
        if(empty($command)){
            return "<?php TemplateCommand::lang(\"$langid\"); ?>";
        }else{
            $commands = implode("&", $command);
            return "<?php TemplateCommand::lang(\"$langid\", '$commands'); ?>";
        }
    }

    public static function parseTemplateCommand($line, $path = NULL) {
        $line = str_replace("$", "_#_", $line);
        $line = explode(" ", $line);
        $command = array_shift($line);
        $args = implode(" ", $line);

        switch ($command) {
            case "set":
                return "<?php $args; ?>";

            case "var":
                return "<?=$args?>";

            case "screen":
                return '<?=TemplateCommand::getScreen()?>';

            case "if":
                return "<?php if($args): ?>";

            case "else":
                return "<?php else: ?>";

            case "elseif":
                return "<?php elseif($args): ?>";

            case "module":
                if(isset($str[1])){
                    return "<?php TemplateCommand::module('$command', \"$args\"); ?>";
                }

                return "<?php TemplateCommand::module('$args'); ?>";

            case "for":
                return "<?php for($args): ?>";

            case "loop":
                return "<?php if(is_array($line[0])||is_object($line[0]))foreach($args): ?>";

            case "loopend":
            case "endloop":
            case "/loop":
                return "<?php endforeach; ?>";
            case "/for":
            case "endfor":
            case "forend":
                return "<?php endfor; ?>";
            case "/if":
            case "endif":
            case "ifend":
                return "<?php endif; ?>";

            default:
                if (method_exists('\Akari\utility\TemplateCommand', $command)) {
                    return "<?php TemplateCommand::$command('$args') ?>";
                }
                throw new TemplateCommandInvalid($command, $args, $path);
        }
    }

    private $data = [];
    public function assign($key, $value) {
        if (!$value && is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } elseif ($value !== NULL) {
            $this->data[ $key ] = $value;
        } elseif ($key === NULL && $value === NULL) {
            return $this->data;
        }
    }
}

Class TemplateNotFound extends \Exception {

    public function __construct($template) {
        $this->message = sprintf("Not Found Template [ %s ]", $template);
    }

}


Class TemplateCommandInvalid extends \Exception {

    public function __construct($commandName, $args, $file = NULL) {
        $file = str_replace(Context::$appEntryPath, '', $file);
        $this->message = sprintf("Template Command Invalid: [ %s ] with [ %s ] on [ %s ]", $commandName, $args, $file);
    }

}
