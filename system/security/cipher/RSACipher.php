<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 23:05
 */

namespace Akari\system\security\cipher;

use Akari\system\util\TextUtil;
use Akari\exception\AkariException;

class RSACipher extends BaseCipher {

    protected $pteKeyRes;
    protected $pubKeyRes;
    protected $blockSize = 30;

    public function __construct(array $options) {
        parent::__construct($options);

        $pubKey = $this->getOption("public_key");
        $pteKey = $this->getOption("private_key");

        if (empty($pubKey) || empty($pteKey)) {
            throw new AkariException("RSACipher NEED PUBLIC KEY AND PRIVATE KEY");
        }

        $this->pubKeyRes = openssl_get_publickey($pubKey);
        $this->pteKeyRes = openssl_get_privatekey($pteKey);
    }

    public function __destruct() {
        if ($this->pteKeyRes) openssl_free_key($this->pteKeyRes);
        if ($this->pubKeyRes) openssl_free_key($this->pubKeyRes);
    }

    public function encrypt($text) {
        $blocks = TextUtil::cutBlock($text, 0, $this->blockSize);
        $chr = NULL;
        $result = [];

        foreach ($blocks as $n => $block) {
            if (!openssl_public_encrypt($block, $chr, $this->pubKeyRes)) {
                throw new AkariException("OpenSSL encrypt failed. " . openssl_error_string());
            }

            $result[] = base64_encode($chr);
        }

        return implode(",", $result);
    }

    public function decrypt($text) {
        $blocks = explode(",", $text);
        $result = "";
        $chr = "";

        foreach ($blocks as $n => $block) {
            $decode = base64_decode($block);
            if (!openssl_private_decrypt($decode, $chr, $this->pteKeyRes)) {
                throw new AkariException("OpenSSL decrypt failed. " . openssl_error_string());
            }
            $result .= $chr;
        }

        return $result;
    }

    /**
     * 签名
     *
     * @param string $str 要签名的内容
     * @param bool $base64 是否返回base64签名过的，默认是
     * @return string
     */
    public function sign($str, $base64 = TRUE) {
        openssl_sign($str, $signature, $this->pteKeyRes);

        return $base64 ? base64_encode($signature) : $signature;
    }

    /**
     * 签名验证
     *
     * @param string $str 要确认的内容
     * @param string $signature 签名
     * @param bool $base64 签名内容是否base64
     * @return bool
     */
    public function verify($str, $signature, $base64 = TRUE) {
        if ($base64)    $signature = base64_decode($signature);

        return openssl_verify($str, $signature, $this->pubKeyRes) == 1;
    }

}
