<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/14
 * Time: 下午10:41
 */

namespace Akari\system\tpl\engine;


class ShinobuTemplateEngine extends BaseTemplateEngine {

    const REGEX_NAME = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A';
    const REGEX_NUMBER = '/[0-9]+(?:\.[0-9]+)?/A';
    const REGEX_STRING = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As';
    const REGEX_DQ_STRING_DELIM = '/"/A';
    const REGEX_DQ_STRING_PART = '/[^#"\\\\]*(?:(?:\\\\.|#(?!\{))[^#"\\\\]*)*/As';
    const PUNCTUATION = '()[]{}?:.,|';

    public function parse($tplPath, array $data, $type, $onlyCompile = false) {
        $this->engineArgs = $data;
        $cachePath = $this->getCachePath($tplPath);

        if (filemtime($tplPath) > filemtime($cachePath) || !file_exists($cachePath)) {
            $template = file_get_contents($tplPath);
            // {{ value }} -> {{ echo $value }}

            $that = $this;
            $template = preg_replace_callback('/\{\{(.*?)\}\}/iu', function($matches) use($that) {
                return $that->parseVal($matches[1]);
            }, $template);

            var_dump($template);die;
        }
    }

    public function getResult($layoutResult, $screenResult) {
        if (empty($screenResult)) {
            return $layoutResult;
        }

        return str_replace("{% screen %}", $screenResult, $layoutResult);
    }

    private function parseVal($command) {

        $options = array_merge(array(
            'tag_comment' => array('{#', '#}'),
            'tag_block' => array('{%', '%}'),
            'tag_variable' => array('{{', '}}'),
            'whitespace_trim' => '-',
            'interpolation' => array('#{', '}'),
        ), []);

        $regexes = array(
            'lex_var' => '/\s*'.preg_quote($options['whitespace_trim'].$options['tag_variable'][1], '/').'\s*|\s*'.preg_quote($options['tag_variable'][1], '/').'/A',
            'lex_block' => '/\s*(?:'.preg_quote($options['whitespace_trim'].$options['tag_block'][1], '/').'\s*|\s*'.preg_quote($options['tag_block'][1], '/').')\n?/A',
            'lex_raw_data' => '/('.preg_quote($options['tag_block'][0].$options['whitespace_trim'], '/').'|'.preg_quote($options['tag_block'][0], '/').')\s*(?:end%s)\s*(?:'.preg_quote($options['whitespace_trim'].$options['tag_block'][1], '/').'\s*|\s*'.preg_quote($options['tag_block'][1], '/').')/s',
            'lex_comment' => '/(?:'.preg_quote($options['whitespace_trim'], '/').preg_quote($options['tag_comment'][1], '/').'\s*|'.preg_quote($options['tag_comment'][1], '/').')\n?/s',
            'lex_block_raw' => '/\s*(raw|verbatim)\s*(?:'.preg_quote($options['whitespace_trim'].$options['tag_block'][1], '/').'\s*|\s*'.preg_quote($options['tag_block'][1], '/').')/As',
            'lex_block_line' => '/\s*line\s+(\d+)\s*'.preg_quote($options['tag_block'][1], '/').'/As',
            'lex_tokens_start' => '/('.preg_quote($options['tag_variable'][0], '/').'|'.preg_quote($options['tag_block'][0], '/').'|'.preg_quote($options['tag_comment'][0], '/').')('.preg_quote($options['whitespace_trim'], '/').')?/s',
            'interpolation_start' => '/'.preg_quote($options['interpolation'][0], '/').'\s*/A',
            'interpolation_end' => '/\s*'.preg_quote($options['interpolation'][1], '/').'/A',
        );

        preg_match_all($regexes['lex_tokens_start'], "Hello, {{ test }} You can {{ get }}", $matches, PREG_OFFSET_CAPTURE);
        var_dump($matches);die;

        return '<?='. $command .'?>';
    }
}