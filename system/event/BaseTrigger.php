<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 09:18
 */

namespace Akari\system\event;

use Akari\system\http\Request;
use Akari\system\http\Response;
use Akari\system\ioc\DIHelper;
use Akari\system\tpl\TemplateHelper;
use Akari\utility\helper\ExceptionSetter;
use Akari\utility\helper\Logging;
use Akari\utility\helper\ResultHelper;
use Akari\utility\helper\ValueHelper;

abstract Class BaseTrigger {

    use ResultHelper, Logging, ExceptionSetter, ValueHelper, DIHelper;

    /** @var  Request */
    protected $request;
    
    /** @var  Response */
    protected $response;
    
    public function __construct() {
        $this->request = $this->_getDI()->getShared("request");
        $this->response = $this->_getDI()->getShared('response');
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

    protected function stop() {
        throw new StopEventBubbling();
    }

}