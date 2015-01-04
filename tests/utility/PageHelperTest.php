<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 16:35
 */

namespace Akari\tests\utility;

require_once __DIR__ . "/../../TestLoader.php";
\Akari\TestLoader::initForTest(__NAMESPACE__, '\Akari\config\BaseConfig');

use Akari\utility\PageHelper;
use PHPUnit_Framework_TestCase;

class PageHelperTest extends \PHPUnit_Framework_TestCase {

    public function testAddParams() {
        $helper = PageHelper::getInstance();
        $helper->init("test", 1, 200, [], 20);
        $this->assertEquals($helper->getLength(), 20);
        $this->assertEquals($helper->getStart(), 0);
    }

}
