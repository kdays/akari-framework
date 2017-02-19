<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/31
 * Time: 下午9:47.
 */

namespace Akari\system\security\cipher;

class XorCipher extends Cipher
{
    private $_secret;

    public function __construct(array $opts)
    {
        parent::__construct($opts);

        $this->_secret = md5($this->getOption('secret', 'Akari'));
    }

    public function encrypt($text)
    {
        $code = '';
        $keyLen = strlen($this->_secret);
        $strLen = strlen($text);
        for ($i = 0; $i < $strLen; $i++) {
            $k = $i % $keyLen;
            $code .= $text[$i] ^ $this->_secret[$k];
        }

        return base64_encode($code);
    }

    public function decrypt($text)
    {
        $code = '';
        $str = base64_decode($text);
        $keyLen = strlen($this->_secret);
        $strLen = strlen($str);
        for ($i = 0; $i < $strLen; $i++) {
            $k = $i % $keyLen;
            $code .= $str[$i] ^ $this->_secret[$k];
        }

        return $code;
    }
}
