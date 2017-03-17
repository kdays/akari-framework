<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/1/4
 * Time: 10:52
 */

namespace Akari\tests\security;

use PHPUnit\Framework\TestCase;
use Akari\system\security\Cipher\RSACipher;

require_once __DIR__ . "/../../testLoader.php";
\Akari\TestLoader::initForTest(__NAMESPACE__, '\Akari\config\BaseConfig');

class RSACipherTest extends TestCase {

    public $publicKey = <<<'EOT'
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDNdAILrsV4h23n0zbNSvbhqlJa
XU/bDIvZ1nu40cyuiDS54TI5gxcKJUDjLdJs115egZ9vOVHiOqe0umWirsNv6P0a
2t8+lXX/Wge/s00krxn9uGr7pgURoA8b3tAO8K6pTeRbq3V/1VQGsqnTbSmLpBDz
EY4Y+RPvqHDibrar6QIDAQAB
-----END PUBLIC KEY-----
EOT;

    public $privateKey = <<<'EOT'
-----BEGIN RSA PRIVATE KEY-----
MIICXgIBAAKBgQDNdAILrsV4h23n0zbNSvbhqlJaXU/bDIvZ1nu40cyuiDS54TI5
gxcKJUDjLdJs115egZ9vOVHiOqe0umWirsNv6P0a2t8+lXX/Wge/s00krxn9uGr7
pgURoA8b3tAO8K6pTeRbq3V/1VQGsqnTbSmLpBDzEY4Y+RPvqHDibrar6QIDAQAB
AoGAHqpwG7pMcz8TooSeK2pDC0/W1vISl0l6HlurP9zgxjRCWnRIgNkWOUdyNfaC
8Af9Z/HFEF7n3/KNUaZ4wR2AwhD09CHw1YpXqQG4TfREpM++z3Lqml9emgVnKsPm
OmeaY03AzTwice2afR1VKp9+ulaM3z8qXX/ypblq6Bw1uQECQQDpyqeyS4gqvksG
qca0crbL//Bs3wHB5790fob5VpqoO8RyFqD47BZUyCqeREub51JTTIAQUpcG+mqq
yRKtWVdZAkEA4Pg31Swkqyuav0KN8XAzdt7SrifrLxf/hggE+6Qarfg8Nkxry4MG
XfAgVklfScPFton9V1f2tdnvCDY+bWn3EQJBAOENOvbP7Mkwm2pTnjrwPnUL7/Xt
inSNUOikL+vvaTtPJWCp1dUo9qowcY4esiXmvIIBHzoXNtj50BqNKpSCbykCQQDd
rXlGwyK20FbB1BEOMaNkpJgxKACk/R66sbhHRiNL/elHD/LALLHfarhSjiYpB5IR
FtPedz0RYFgbXWgSZHIhAkEAqy5M+uQ1pmv5bYUBnyjw4xD+8gRL/suFeRzUAu0i
6sFK+a6UO5jzxGiyOuIDaUzw7YJn4Yv7gt6cUjIKVgKWbA==
-----END RSA PRIVATE KEY-----
EOT;

    public function testEncrypt() {
        /**
         * @var $cipher RSACipher
         */
        $cipher = new RSACipher([
            'public_key' => $this->publicKey,
            'private_key' => $this->privateKey
        ]);

        $text = "我是一段测试文字";
        $encrypt = $cipher->encrypt($text);

        $this->assertEquals($text, $cipher->decrypt($encrypt));
    }

}
