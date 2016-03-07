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
    
    private $_cipher = MCRYPT_RIJNDAEL_256;
    private $_mode = MCRYPT_MODE_ECB;
    private $_iv;
    private $_secret;
    
    public function __construct(array $opts) {
        parent::__construct($opts);
        
        if (!function_exists('mcrypt_module_open')) {
            throw new EncryptFailed("please install MCrypt.");
        }
        
        $this->_iv = $this->getOption('iv');
        $this->_secret = md5($this->getOption('secret', Context::$appConfig->appName));
    }
    
    public function encrypt($text) {
        $td = mcrypt_module_open($this->_cipher, '', $this->_mode, '');
        
        $blockSize = mcrypt_get_block_size($this->_cipher, $this->_mode);
        $text = CipherUtil::pkcs5_pad($text, $blockSize);

        $iv = $this->_iv;
        if (empty($iv)) {
            $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        }

        mcrypt_generic_init($td, $this->_secret, $iv);
        $encrypted = mcrypt_generic($td, $text);
        $result = bin2hex($encrypted);
        
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        
        return $result;
    }

    public function decrypt($text) {
        $td = mcrypt_module_open($this->_cipher, '', $this->_mode, '');
        $iv = $this->_iv;
        if (empty($iv)) {
            $iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
        }
        
        mcrypt_generic_init($td, $this->_secret, $iv);
        $decryptedText = mdecrypt_generic($td, hex2bin($text));
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        
        return CipherUtil::pkcs5_unpad($decryptedText);
    }
}