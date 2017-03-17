<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 17/3/16
 * Time: 下午5:26
 */

namespace Akari\system\security;

use Akari\system\exception\AkariException;

/**
 * Class SafeHTML
 * 富媒体编辑器时 对HTML各个属性和标签进行安全过滤
 *
 * @package Akari\system\security
 */
class SafeHTML {

    protected $DOM;
    protected $allowAttr = ['title', 'src', 'href', 'id', 'class', 'style', 'width', 'height', 'alt', 'target', 'align'];
    protected $allowTag = ['a', 'img', 'br', 'strong', 'b', 'code', 'pre', 'p', 'div', 'em', 'span', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'table', 'ul', 'ol', 'tr', 'th', 'td', 'hr', 'li', 'u'];

    public function __construct($html, $allowTag, $charset) {
        if (!empty($allowTag)) {
            $this->allowTag = $allowTag;
        }

        $html = strip_tags($html, '<' . implode('><', $this->allowTag) . '>');
        $html = <<<EOT
<meta http-equiv="Content-Type" content="text/html;charset={$charset}"><nouse>$html</nouse>
EOT;

        $DOM = new \DOMDocument();
        $DOM->strictErrorChecking = FALSE;
        if (!$DOM->loadHTML($html)) {
            throw new AkariException("[XSS Security] Wrong HTML Stub");
        }

        $this->DOM = $DOM;
    }

    public static function filter($html, $allowTags = [], $charset = 'utf-8') {
        $filter = new self($html, $allowTags, $charset);

        return $filter->getResult();
    }

    public function getResult() {
        if (empty($this->DOM)) return '';

        $nodes = $this->DOM->getElementsByTagName("*");
        for ($i = 0; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            if (in_array($node->nodeName, $this->allowTag)) {
                $methodName = "doFilter" . ucfirst($node->nodeName);
                if (!method_exists($this, $methodName)) {
                    $methodName = 'doFilterDefaultNode';
                }

                call_user_func([$this, $methodName], $node);
            }
        }

        $html = strip_tags($this->DOM->saveHTML(), '<' . implode('><', $this->allowTag) . '>');
        $html = preg_replace('/^\n(.*)\n$/s', '$1', $html);

        return $html;
    }

    private function safeStyle(\DOMElement $node) {
        if ($node->attributes->getNamedItem('style')) {
            $style = $node->attributes->getNamedItem('style')->nodeValue;
            $style = str_replace('\\', ' ', $style);
            $style = str_replace(['&#', '/*', '*/'], ' ', $style);
            $style = preg_replace('#e.*x.*p.*r.*e.*s.*s.*i.*o.*n#Uis', ' ', $style);

            return $style;
        } 
        
        return '';
    }

    private function safeUrl($url) {
        if (preg_match('#^https?://.+#is', $url)) {
            return $url;
        } else {
            return 'http://' . $url;
        }
    }

    private function safeLinkEle(\DOMElement $node, $att) {
        $link = $node->attributes->getNamedItem($att);
        if ($link) {
            return $this->safeUrl($link->nodeValue);
        }

        return '';
    }

    private function setAttr(\DOMElement $node, $attr, $val) {
        if (!empty($val)) {
            $node->setAttribute($attr, $val);
        }
    }

    private function setDefaultAttr(\DOMElement $node, $attr, $default = '') {
        $o = $node->attributes->getNamedItem($attr);
        if ($o) {
            $this->setAttr($node, $attr, $o->nodeValue);
        } else {
            $this->setAttr($node, $attr, $default);
        }
    }

    private function commonAttrFilter(\DOMElement $node) {
        $removeAttr = [];
        foreach ($node->attributes as $attr) {
            if (!in_array($attr->nodeName, $this->allowAttr)) {
                $removeAttr[] = $attr->nodeName;
            }
        }

        foreach ($removeAttr as $attr) {
            $node->removeAttribute($attr);
        }

        $style = $this->safeStyle($node);
        $this->setAttr($node, 'style', $style);
        $this->setDefaultAttr($node, 'title');
        $this->setDefaultAttr($node, 'id');
        $this->setDefaultAttr($node, 'class');
    }

    private function doFilterImg(\DOMElement $node) {
        $this->commonAttrFilter($node);

        $this->setDefaultAttr($node, 'src');
        $this->setDefaultAttr($node, 'width');
        $this->setDefaultAttr($node, 'height');
        $this->setDefaultAttr($node, 'alt');
        $this->setDefaultAttr($node, 'align');
    }

    private function doFilterA(\DOMElement $node) {
        $this->commonAttrFilter($node);

        $href = $this->safeLinkEle($node, 'href');
        $this->setAttr($node, 'href', $href);
        $this->setDefaultAttr($node, 'target', '_blank');
    }

    private function doFilterEmbed(\DOMElement $node) {
        $this->commonAttrFilter($node);
        $link = $this->safeLinkEle($node, 'src');

        $this->setAttr($node, 'src', $link);
        $this->setAttr($node, 'allowscriptaccess', 'never');
        $this->setDefaultAttr($node, 'width');
        $this->setDefaultAttr($node, 'height');
    }

    private function doFilterDefaultNode(\DOMElement $node) {
        $this->commonAttrFilter($node);
    }

}
