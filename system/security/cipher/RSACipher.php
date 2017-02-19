<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/31
 * Time: 下午9:49.
 */

namespace Akari\system\security\cipher;

class RSACipher extends Cipher
{
    const BLOCK_LENGTH = 30;

    private $_pteKey;
    private $_pubKey;
    private $_pubKeyRes = null;
    private $_pteKeyRes = null;

    public function __construct(array $opts)
    {
        parent::__construct($opts);

        if (!function_exists('openssl_free_key')) {
            throw new EncryptFailed('OPENSSL not installed');
        }

        $this->loadPublicKey($this->getOption('public_key'));
        $this->loadPrivateKey($this->getOption('private_key'));
    }

    public function __destruct()
    {
        if ($this->_pteKeyRes != null) {
            openssl_free_key($this->_pteKeyRes);
        }
        if ($this->_pubKeyRes != null) {
            openssl_free_key($this->_pubKeyRes);
        }
    }

    /**
     * 签名.
     *
     * @param string $str    要签名的内容
     * @param bool   $base64 是否返回base64签名过的，默认是
     *
     * @return string
     */
    public function sign($str, $base64 = true)
    {
        openssl_sign($str, $signature, $this->getPrivateKeyRes());

        return $base64 ? base64_encode($signature) : $signature;
    }

    /**
     * 签名验证
     *
     * @param string $str       要确认的内容
     * @param string $signature 签名
     * @param bool   $base64    签名内容是否base64
     *
     * @return int
     */
    public function verify($str, $signature, $base64 = true)
    {
        if ($base64) {
            $signature = base64_decode($signature);
        }

        return openssl_verify($str, $signature, $this->getPublicKeyRes()) == 1;
    }

    /**
     * 加密.
     *
     * @param string $str 内容
     *
     * @throws EncryptFailed
     *
     * @return string
     */
    public function encrypt($str)
    {
        $res = $this->getPublicKeyRes();
        $blocks = CipherUtil::makeTextBlocks($str, 0, self::BLOCK_LENGTH);
        $chr = null;
        $encodes = [];
        foreach ($blocks as $n => $block) {
            if (!openssl_public_encrypt($block, $chr, $res)) {
                throw new EncryptFailed('OpenSSL encrypt failed. '.openssl_error_string());
            }
            $encodes[] = base64_encode($chr);
        }

        return implode(',', $encodes);
    }

    /**
     * 解密.
     *
     * @param string $str 密文
     *
     * @throws \Exception
     *
     * @return string
     */
    public function decrypt($str)
    {
        $res = $this->getPrivateKeyRes();
        $decodes = explode(',', $str);
        $result = '';
        $chr = '';
        foreach ($decodes as $n => $decode) {
            $decode = base64_decode($decode);
            if (!openssl_private_decrypt($decode, $chr, $res)) {
                throw new EncryptFailed('OpenSSL decrypt failed. '.openssl_error_string());
            }
            $result .= $chr;
        }

        return $result;
    }

    /**
     * 获得Public RSA密匙资源.
     *
     * @throws EncryptFailed
     *
     * @return null|resource
     */
    private function getPublicKeyRes()
    {
        if ($this->_pubKeyRes == null) {
            if ($this->_pubKey == null) {
                throw new EncryptFailed('Please set RSA public key');
            }
            $this->_pubKeyRes = openssl_get_publickey($this->_pubKey);
        }

        return $this->_pubKeyRes;
    }

    /**
     * 获得Private RSA密匙资源.
     *
     * @throws EncryptFailed
     *
     * @return bool|null|resource
     */
    private function getPrivateKeyRes()
    {
        if ($this->_pteKeyRes == null) {
            if ($this->_pteKey == null) {
                throw new EncryptFailed('Please set RSA private key');
            }
            $this->_pteKeyRes = openssl_get_privatekey($this->_pteKey);
        }

        return $this->_pteKeyRes;
    }

    /**
     * 载入新的公钥.
     *
     * @param string $key 公钥内容 或 公钥文件路径
     *
     * @throws EncryptFailed
     *
     * @return null|resource
     */
    public function loadPublicKey($key)
    {
        $this->_pubKey = $key;
        if (!in_string($key, '---')) {
            if (!file_exists($key)) {
                throw new EncryptFailed('public key load failed');
            }
            $this->_pubKey = file_get_contents($key);
        }
        if ($this->_pubKeyRes != null) {
            openssl_free_key($this->_pubKeyRes);
        }
        $this->_pubKeyRes = null;

        return $this->getPublicKeyRes();
    }

    /**
     * 载入新的私钥.
     *
     * @param string $key 私钥内容 或 私钥文件路径
     *
     * @throws EncryptFailed
     *
     * @return bool|null|resource
     */
    public function loadPrivateKey($key)
    {
        $this->_pteKey = $key;
        if (!in_string($key, '---')) {
            if (!file_exists($key)) {
                throw new EncryptFailed('private key load failed');
            }
            $this->_pteKey = file_get_contents($key);
        }
        if ($this->_pteKeyRes != null) {
            openssl_free_key($this->_pteKeyRes);
        }
        $this->_pteKeyRes = null;

        return $this->getPrivateKeyRes();
    }
}
