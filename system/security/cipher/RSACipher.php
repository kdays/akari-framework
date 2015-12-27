<?php
namespace Akari\system\security\Cipher;

use Akari\Context;

Class RSACipher extends Cipher {

    const BLOCK_LENGTH = 30;

    private $_pteKey;
    private $_pubKey;
    private $_pubKeyRes = NULL;
    private $_pteKeyRes = NULL;
    protected static $d = NULL;

    public static function getInstance($mode = 'default'){
        return self::_instance($mode);
    }

    protected function __construct($mode) {
        $config = $this->getConfig($mode);

        if (isset($config['public_key']))   $this->loadPublicKey($config['public_key']);
        if (isset($config['private_key']))   $this->loadPrivateKey($config['private_key']);
    }

    public function __destruct() {
        if ($this->_pteKeyRes != NULL)  openssl_free_key($this->_pteKeyRes);
        if ($this->_pubKeyRes != NULL)  openssl_free_key($this->_pubKeyRes);
    }

    /**
     * 载入新的公钥
     *
     * @param string $key 公钥内容 或 公钥文件路径
     * @return null|resource
     * @throws RSAException
     */
    public function loadPublicKey($key) {
        $this->_pubKey = $key;
        if (!in_string($key, '---')) {
            if (!file_exists($key)) throw new RSAException("public key load failed");
            $this->_pubKey = file_get_contents($key);
        }

        if ($this->_pubKeyRes != NULL)  openssl_free_key($this->_pubKeyRes);
        $this->_pubKeyRes = NULL;

        return $this->getPublicKeyRes();
    }

    /**
     * 载入新的私钥
     *
     * @param string $key 私钥内容 或 私钥文件路径
     * @return bool|null|resource
     * @throws RSAException
     */
    public function loadPrivateKey($key) {
        $this->_pteKey = $key;
        if (!in_string($key, '---')) {
            if (!file_exists($key)) throw new RSAException("private key load failed");
            $this->_pteKey = file_get_contents($key);
        }

        if ($this->_pteKeyRes != NULL)  openssl_free_key($this->_pteKeyRes);
        $this->_pteKeyRes = NULL;

        return $this->getPrivateKeyRes();
    }

    /**
     * 签名
     *
     * @param string $str 要签名的内容
     * @param bool $base64 是否返回base64签名过的，默认是
     * @return string
     */
    public function sign($str, $base64 = TRUE) {
        openssl_sign($str, $signature, $this->getPrivateKeyRes());
        return $base64 ? base64_encode($signature) : $signature;
    }

    /**
     * 签名验证
     *
     * @param string $str 要确认的内容
     * @param string $signature 签名
     * @param bool $base64 签名内容是否base64
     * @return int
     */
    public function verify($str, $signature, $base64 = TRUE) {
        if ($base64)    $signature = base64_decode($signature);
        return openssl_verify($str, $signature, $this->getPublicKeyRes()) == 1;
    }

    /**
     * 加密
     *
     * @param string $str 内容
     * @return string
     * @throws RSAException
     */
    public function encrypt($str) {
        $res = $this->getPublicKeyRes();
        $blocks = $this->makeTextBlocks($str, 0, self::BLOCK_LENGTH);
        $chr = NULL;
        $encodes = [];

        foreach ($blocks as $n => $block) {
            if (!openssl_public_encrypt($block, $chr, $res)) {
                throw new RSAException("OpenSSL encrypt failed. ".openssl_error_string());
            }

            $encodes[] = base64_encode($chr);
        }

        return implode(",", $encodes);
    }

    /**
     * 解密
     *
     * @param string $str 密文
     * @return string
     * @throws \Exception
     */
    public function decrypt($str) {
        $res = $this->getPrivateKeyRes();
        $decodes = explode(",", $str);
        $result = "";
        $chr = "";

        foreach ($decodes as $n => $decode) {
            $decode = base64_decode($decode);
            if (!openssl_private_decrypt($decode, $chr, $res)) {
                throw new RSAException("OpenSSL decrypt failed. ".openssl_error_string());
            }

            $result .= $chr;
        }

        return $result;
    }

    /**
     * 将文字分块
     *
     * @param string $content 内容
     * @param int $n 开始点
     * @param int $blockLength 块大小
     * @return array
     */
    private function makeTextBlocks($content, $n = 0, $blockLength = 30) {
        $arr = array ();
        $len = strlen($content);
        for ($i = $n; $i < $len; $i += $blockLength) {
            $res = $this->subchar($content, $i, $blockLength);
            if (!empty ($res)) {
                $arr[] = $res;
            }
        }

        return $arr;
    }

    /**
     * UTF-8切割文字
     *
     * @param string $str 文字内容
     * @param int $start 开始点
     * @param int $length 大小
     * @return string
     */
    private function subchar($str, $start = 0, $length) {
        if (strlen($str) <= $length) {
            return $str;
        }
        $re = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
        preg_match_all($re, $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
        return $slice;
    }

    /**
     * 获得Public RSA密匙资源
     *
     * @return null|resource
     * @throws RSAException
     */
    private function getPublicKeyRes() {
        if ($this->_pubKeyRes == NULL) {
            if ($this->_pubKey == NULL) throw new RSAException("Please set RSA public key");
            $this->_pubKeyRes = openssl_get_publickey($this->_pubKey);
        }

        return $this->_pubKeyRes;
    }

    /**
     * 获得Private RSA密匙资源
     *
     * @return bool|null|resource
     * @throws RSAException
     */
    private function getPrivateKeyRes() {
        if ($this->_pteKeyRes == NULL) {
            if ($this->_pteKey == NULL) throw new RSAException("Please set RSA private key");
            $this->_pteKeyRes = openssl_get_privatekey($this->_pteKey);
        }

        return $this->_pteKeyRes;
    }
}

Class RSAException extends CipherException {
    public function __construct($message) {
        $this->message = "[Akari.RSACipher] ".$message;
    }
}