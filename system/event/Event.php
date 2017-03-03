<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/4/13
 * Time: 下午2:55
 */

namespace Akari\system\event;

class Event {

    protected $_eventType;

    protected $_data;

    /** @var  EventHandler */
    protected $_nowHandler;

    public function __construct($eventType, $data) {
        $this->_eventType = $eventType;
        $this->_data = $data;
    }

    public function _setListener(EventHandler $handler) {
        $this->_nowHandler = $handler;
    }

    public function _tick() {
        $resultValue = $this->getListener()->_handle($this);

        if ($resultValue !== NULL) {
            if ($resultValue instanceof Event) {
                return $resultValue;
            } else {
                $this->_data = $resultValue;
            }
        }

        return $this;
    }

    public function getListener() {
        return $this->_nowHandler;
    }

    public function setData($data) {
        $this->_data = $data;
    }

    public function getData() {
        return $this->_data;
    }

    public function getType() {
        return $this->_eventType;
    }

    public function getSubType() {
        $l = explode(".", $this->_eventType);

        return array_pop($l);
    }

    public function stop() {
        throw new StopEventBubbling();
    }

}
