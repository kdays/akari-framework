<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 15:44
 */

namespace Akari\tests\utility;

require_once __DIR__ . "/../../TestLoader.php";
\Akari\TestLoader::initForTest(__NAMESPACE__, '\Akari\config\BaseConfig');

use Akari\utility\TemplateHelper;
use PHPUnit_Framework_TestCase;

class TemplateHelperTest extends PHPUnit_Framework_TestCase {

    public function testTemplateText() {
        $helper = TemplateHelper::getInstance();
        $this->assertEquals($helper->parseTemplateText('$a'), '<?=$a?>');
        $this->assertEquals($helper->parseTemplateText('<!--#if $a == $b-->'), '<?php if($a == $b): ?>');
    }

    public function testAssign() {
        $helper = TemplateHelper::getInstance();
        $helper->assign("123", "456");
        $data = $helper->assign(NULL, NULL);

        $this->assertEquals($data['123'], 456);
    }

}
