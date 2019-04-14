<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 23:01
 */

namespace Akari\system\security\cipher;

class AESCipher extends BaseCipher {

    protected $bits;
    protected $secret;

    public function __construct(array $options) {
        parent::__construct($options);

        $this->bits = $this->getOption("bits", 256);
        $this->secret = $this->getOption("secret");
    }

    protected function getOpenSSLMethod() {
        return 'AES-' . $this->bits . "-ECB";
    }

    public function encrypt($text) {
        return openssl_encrypt($text, $this->getOpenSSLMethod(), $this->secret);
    }

    public function decrypt($text) {
        return openssl_decrypt($text, $this->getOpenSSLMethod(), $this->secret);
    }

}
