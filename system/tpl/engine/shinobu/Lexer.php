<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/7/14
 * Time: 下午11:31
 */

namespace Akari\system\tpl\engine\shinobu;


class Lexer {

    protected $options;
    protected $regex;

    ///
    protected $code;
    protected $cursor = 0;
    protected $lineno = 1;
    protected $end;
    protected $tokens;
    protected $state;
    protected $states = [];
    protected $brackets = [];
    protected $position;
    protected $positions = [];
    protected $currentVarBlockLine;

    const REGEX_NAME = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/A';
    const REGEX_NUMBER = '/[0-9]+(?:\.[0-9]+)?/A';
    const REGEX_STRING = '/"([^#"\\\\]*(?:\\\\.[^#"\\\\]*)*)"|\'([^\'\\\\]*(?:\\\\.[^\'\\\\]*)*)\'/As';
    const REGEX_DQ_STRING_DELIM = '/"/A';
    const REGEX_DQ_STRING_PART = '/[^#"\\\\]*(?:(?:\\\\.|#(?!\{))[^#"\\\\]*)*/As';
    const PUNCTUATION = '()[]{}?:.,|';

    public function __construct($options = []) {
        $baseOptions = [
            'tag_comment' => ['{#', '#}'],
            'tag_block' => ['{%', '%}'],
            'tag_var' => ['{{', '}}'],
            'whitespace_trim' => '-'
        ];

        $this->options = $baseOptions + $options;

        $this->regex = [
            'lex_var' => '/\s*'.
                preg_quote($this->options['whitespace_trim']. $this->options['tag_var'][1], '/').
                '\s*|\s*'.
                preg_quote($this->options['tag_var'][1], '/').
                '/A',

            'lex_block' => '/\s*(?:'.
                preg_quote($this->options['whitespace_trim'].$this->options['tag_block'][1], '/').
                '\s*|\s*'.
                preg_quote($this->options['tag_block'][1], '/')
                .')\n?/A',

            'operator' => $this->getOperatorRegex(),

            'lex_block_raw' => '/\s*(raw|verbatim)\s*(?:'.
                preg_quote($this->options['whitespace_trim'].$this->options['tag_block'][1], '/')
                .'\s*|\s*'
                .preg_quote($this->options['tag_block'][1], '/').')/As',

            'lex_block_line' => '/\s*line\s+(\d+)\s*'.
                preg_quote($this->options['tag_block'][1], '/')
                .'/As',

            'lex_tokens_start' => '/('.
                preg_quote($this->options['tag_var'][0], '/').
                "|". preg_quote($this->options['tag_block'][0], '/').
                '|'. preg_quote($this->options['tag_comment'][0], '/').
                ')('. preg_quote($this->options['whitespace_trim'], '/').
                ')?/s'
        ];
    }

    const STATE_DATA = 0;
    const STATE_VAR = 1;
    const STATE_BLOCK = 2;
    const STATE_STR = 3;

    public function tokenize($code) {
        $this->code = str_replace(["\r\n", "\r"], "\n", $code);
        $this->cursor = 0;
        $this->lineno = 1;
        $this->end = strlen($this->code);
        $this->tokens = [];
        $this->state = self::STATE_DATA;
        $this->states = [];
        $this->brackets = [];
        $this->position = -1;

        preg_match_all($this->regex['lex_tokens_start'], $this->code, $matches, PREG_OFFSET_CAPTURE);
        $this->positions = $matches;

        while ($this->cursor < $this->end) {
            switch ($this->state) {
                case self::STATE_DATA:
                    $this->lexData();
                    break;

                case self::STATE_VAR:
                    $this->lexVar();
                    break;

                case self::STATE_STR:
                    $this->lexStr();
                    break;

                case self::STATE_BLOCK:
                    $this->lexBlock();
                    break;
            }
        }

        return $this->tokens;
    }

    protected function lexBlock() {
        if (empty($this->brackets) && preg_match($this->regex['lex_block'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(ShinoToken::TYPE_BLOCK_END);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }

    protected function lexStr() {
        if (preg_match(self::REGEX_DQ_STRING_PART, $this->code, $match, null, $this->cursor) && strlen($match[0]) > 0) {
            $this->pushToken(ShinoToken::TYPE_STR, stripcslashes($match[0]));
            $this->moveCursor($match[0]);
        } elseif (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, null, $this->cursor)) {
            list($expect, $lineno) = array_pop($this->brackets);
            if ($this->code[$this->cursor] != '"') {
                throw new \Exception(sprintf('Unclosed "%s"', $expect));
            }

            $this->popState();
            ++$this->cursor;
        }
    }

    protected function lexVar() {
        if (empty($this->brackets) && preg_match($this->regex['lex_var'], $this->code, $match, NULL, $this->cursor)) {
            $this->pushToken(ShinoToken::TYPE_VAR_END);
            $this->moveCursor($match[0]);
            $this->popState();
        } else {
            $this->lexExpression();
        }
    }

    protected function lexExpression() {
        if (preg_match('/\s+/A', $this->code, $match, null, $this->cursor)) {
            $this->moveCursor($match[0]);
            if ($this->cursor >= $this->end) {
                throw new \Exception('Unclosed '. ($this->state === self::STATE_BLOCK ? 'block' : 'variable'));
            }
        }

        if (preg_match($this->regex['operator'], $this->code, $match, null, $this->cursor)) {
            $this->pushToken(ShinoToken::TYPE_OPER, preg_replace('/\s+/', ' ', $match[0]));
            $this->moveCursor($match[0]);
        }elseif (preg_match(self::REGEX_NAME, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(ShinoToken::TYPE_NAME, $match[0]);
            $this->moveCursor($match[0]);
        } elseif (preg_match(self::REGEX_NUMBER, $this->code, $match, null, $this->cursor)) {
            $number = (float) $match[0];  // floats
            if (ctype_digit($match[0]) && $number <= PHP_INT_MAX) {
                $number = (int) $match[0]; // integers lower than the maximum
            }
            $this->pushToken(ShinoToken::TYPE_NUM, $number);
            $this->moveCursor($match[0]);
        } elseif (false !== strpos(self::PUNCTUATION, $this->code[$this->cursor])) {
            // opening bracket
            if (false !== strpos('([{', $this->code[$this->cursor])) {
                $this->brackets[] = array($this->code[$this->cursor], $this->lineno);
            } elseif (false !== strpos(')]}', $this->code[$this->cursor])) {
                if (empty($this->brackets)) {
                    throw new \Exception(sprintf('Unexpected "%s"', $this->code[$this->cursor]));
                }
                list($expect, $lineno) = array_pop($this->brackets);

                if ($this->code[$this->cursor] != strtr($expect, '([{', ')]}')) {
                    throw new \Exception(sprintf('Unclosed "%s"', $expect));
                }
            }

            $this->pushToken(ShinoToken::TYPE_PUNCTUATION, $this->code[$this->cursor]);
            ++$this->cursor;
        } elseif (preg_match(self::REGEX_STRING, $this->code, $match, null, $this->cursor)) {
            $this->pushToken(ShinoToken::TYPE_STR, stripcslashes(substr($match[0], 1, -1)));
            $this->moveCursor($match[0]);
        } elseif (preg_match(self::REGEX_DQ_STRING_DELIM, $this->code, $match, null, $this->cursor)) {
            $this->brackets[] = array('"', $this->lineno);
            $this->pushState(self::STATE_STR);
            $this->moveCursor($match[0]);
        } else {
            echo "ERROR";
            var_dump($this->code[$this->cursor]);
            die;
        }
    }

    protected function lexData() {

        // 纯文字 后面没有任何匹配情况下 直接结束
        if ($this->position == count($this->positions[0]) - 1) {
            $this->pushToken(ShinoToken::TYPE_TEXT, substr($this->code, $this->cursor));
            $this->cursor = $this->end;

            return ;
        }

        $pos = $this->positions[0][++$this->position];
        while($this->positions[1] < $this->cursor) {
            if ($this->position == count($this->positions[0]) - 1) {
                return ;
            }

            $pos = $this->positions[0][++$this->position];
        }

        // 查找文字
        $text = $textContent = substr($this->code, $this->cursor, $pos[1] - $this->cursor);
        if (isset($this->positions[2][$this->position][0])) {
            $text = rtrim($text);
        }
        $this->pushToken(ShinoToken::TYPE_TEXT, $text);
        $this->moveCursor($textContent.$pos[0]); //$pos[0]刚好是控制符开始的位置

        switch ($this->positions[1][$this->position][0]) { // 取指令符开始
            case $this->options['tag_var'][0]:
                $this->pushToken(ShinoToken::TYPE_VAR);
                $this->pushState(self::STATE_VAR);
                $this->currentVarBlockLine = $this->lineno;
                break;

            case $this->options['tag_block'][0]:
                if (preg_match($this->regex['lex_block_raw'], $this->code, $match, null, $this->cursor)) {
                    $this->moveCursor($match[0]);
                    $this->lexRawData($match[1]);
                    // {% line \d+ %}
                } elseif (preg_match($this->regex['lex_block_line'], $this->code, $match, null, $this->cursor)) {
                    $this->moveCursor($match[0]);
                    $this->lineno = (int) $match[1];
                } else {
                    $this->pushToken(ShinoToken::TYPE_BLOCK);
                    $this->pushState(self::STATE_BLOCK);
                    $this->currentVarBlockLine = $this->lineno;
                }
                break;
        }
    }

    protected function pushToken($type, $value = '') {
        if ($type == ShinoToken::TYPE_TEXT && $value == '') {
            return ;
        }

        $this->tokens[] = new ShinoToken($type, $value, $this->lineno);
    }

    protected function moveCursor($text) {
        $this->cursor += strlen($text);
        $this->lineno += substr_count($text, "\n");
    }

    protected function pushState($state) {
        $this->states[] = $this->state;
        $this->state = $state;
    }

    protected function popState() {
        $this->state = array_pop($this->states);
    }

    protected function getOperatorRegex() {
        $operators = array_merge(
            ['='],
            ShinoOperator::getBaseOper(),
            ShinoOperator::getFuncOper()
        );
        $operators = array_combine($operators, array_map('strlen', $operators));
        arsort($operators);
        $regex = array();
        foreach ($operators as $operator => $length) {
            if (ctype_alpha($operator[$length - 1])) {
                $r = preg_quote($operator, '/').'(?=[\s()])';
            } else {
                $r = preg_quote($operator, '/');
            }
            // an operator with a space can be any amount of whitespaces
            $r = preg_replace('/\s+/', '\s+', $r);
            $regex[] = $r;
        }
        return '/'.implode('|', $regex).'/A';
    }
}