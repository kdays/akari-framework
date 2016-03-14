<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/5/19
 * Time: 下午10:04
 */

namespace Akari\system\result;

use Akari\system\http\Request;
use Akari\system\http\Response;
use Akari\system\ioc\DIHelper;
use Akari\system\tpl\TemplateHelper;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\ValueHelper;

abstract class Widget {

    use ResultHelper, ValueHelper, Logging, DIHelper;
    
    /** @var Request $request */
    protected $request;
    
    /** @var  Response */
    protected $response;
    
    public function __construct() {
        $this->request = $this->_getDI()->getShared("request");
        $this->response = $this->_getDI()->getShared("response");
    }

    /**
     * 绑定模板参数
     * 
     * @param string $key
     * @param mixed $value
     */
    protected function _bindVar($key, $value = NULL) {
        TemplateHelper::assign($key, $value);
    }

    /**
     * @param mixed $userData
     * @return array
     */
    abstract public function execute($userData = NULL);

}