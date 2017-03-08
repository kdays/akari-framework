<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/1/22
 * Time: 下午10:17
 */

namespace Akari\system\event;

class EventHandler {

    protected $handler;

    protected $priority = 0;

    protected $_eventType;

    protected $_id;

    public function __construct($handler, $eventType, $id, $priority = 0) {
        $this->_eventType = $eventType;
        $this->_id = $id;
        $this->handler = $handler;
        $this->priority = $priority;
    }

    public function getId() {
        return $this->_id;
    }

    public function detach() {
        Listener::detachById($this->_id);
    }

    public function getPriority() {
        return $this->priority;
    }

    public function getType() {
        return $this->_eventType;
    }

    public function setHandler($handler) {
        $this->handler = $handler;
    }

    public function getHandler() {
        return $this->handler;
    }

    public function _handle(Event $event) {
        // 先判断一下handler的类型
        if (is_callable($this->handler)) {
            return call_user_func_array($this->handler, [$event]);
        }

        // 不然的话假设是Class
        $method = $event->getSubType() . 'Action';
        if (method_exists($this->handler, $method)) {
            return call_user_func_array([$this->handler, $method], [$event]);
        } elseif (method_exists($this->handler, 'handle')) {
            return call_user_func_array([$this->handler, 'handle'], [$event]);
        }
    }
}
