<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/1/4
 * Time: 11:22.
 */

namespace Akari\tests\security;

use Akari\system\security\cipher\AESCipher;

require_once __DIR__.'/../../testLoader.php';
\Akari\TestLoader::initForTest(__NAMESPACE__, '\Akari\config\BaseConfig');

class AESCipherTest extends \PHPUnit_Framework_TestCase
{
    public function testCipher()
    {
        $cipher = new AESCipher([
            'secret' => 'test',
        ]);

        $text = '这是一段测试文字';
        $encrypt = $cipher->encrypt($text);
        $this->assertEquals($text, $cipher->decrypt($encrypt));
    }
}
