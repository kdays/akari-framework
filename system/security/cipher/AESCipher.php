<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/31
 * Time: 下午9:29
 */

namespace Akari\system\security\cipher;

use Akari\Context;

class AESCipher extends Cipher{ 

    private $_secret;
    private $_method;
    private $_bySSL = FALSE;

    public function __construct(array $opts) {
        parent::__construct($opts);

        $bits = $this->getOption('bits', 256);
        if (function_exists("openssl_encrypt")) {
            $this->_method = 'AES-' . $bits . "-ECB";
            $this->_bySSL = TRUE;
        } elseif (function_exists('mcrypt_module_open')) {
            $this->_method = defined('MCRYPT_RIJNDAEL_' . $bits) ? constant('MCRYPT_RIJNDAEL_' . $bits) : MCRYPT_RIJNDAEL_128;
        } else {
            throw new EncryptFailed("need install openSSL or mcrypt");
        }

        $this->_secret = md5($this->getOption('secret', Context::$appConfig->appName));

    }

    protected function openSSLEncrypt(string $text) {
        return openssl_encrypt($text, $this->_method, $this->_secret);
    }

    protected function openSSLDecrypt(string $text) {
        return openssl_decrypt($text, $this->_method, $this->_secret);
    }

    protected function mcryptEncrypt($text) {
        $sCipher = MCRYPT_RIJNDAEL_256;
        $sMode = MCRYPT_MODE_ECB;

        $td = mcrypt_module_open($sCipher, '', $sMode, '');

        $blockSize = mcrypt_get_block_size($sCipher, $sMode);
        $text = CipherUtil::pkcs5_pad($text, $blockSize);

        $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        mcrypt_generic_init($td, $this->_secret, $iv);
        $encrypted = mcrypt_generic($td, $text);
        $result = bin2hex($encrypted);

        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return $result;
    }

    protected function mcryptDecrypt($text) {
        $sCipher = MCRYPT_RIJNDAEL_256;
        $sMode = MCRYPT_MODE_ECB;

        $td = mcrypt_module_open($sCipher, '', $sMode, '');
        $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);

        mcrypt_generic_init($td, $this->_secret, $iv);
        $decryptedText = mdecrypt_generic($td, hex2bin($text));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);

        return CipherUtil::pkcs5_unpad($decryptedText);
    }

    public function encrypt($text) {
        // TODO: Implement encrypt() method.
        if ($this->_bySSL) {
            return $this->openSSLEncrypt($text);
        }

        return $this->mcryptEncrypt($text);
    }

    public function decrypt($text) {
        // TODO: Implement decrypt() method.
        if ($this->_bySSL) {
            return $this->openSSLDecrypt($text);
        }

        return $this->mcryptDecrypt($text);
    }
}
