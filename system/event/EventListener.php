<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/24
 * Time: 1:32
 */

namespace Akari\system\event;


class EventListener {

    protected $handler;

    protected $priority;

    protected $_eventType;

    protected $_eventId;

    protected $_debug;

    public function __construct($handler, string $eventType, int $priority, int $eventId) {
        $this->handler = $handler;
        $this->priority = $priority;
        $this->_eventType = $eventType;
        $this->_eventId = $eventId;
    }

    public function setDebug($file, $line) {
        $this->_debug = [$file, $line];
    }

    public function getHandler() {
        return $this->handler;
    }

    public function getPriority() {
        return $this->priority;
    }

    public function getType() {
        return $this->_eventType;
    }

    public function getId() {
        return $this->_eventId;
    }

    public function fire($parameters) {
        call_user_func_array($this->getHandler(), [$this, $parameters]);
        if ($parameters instanceof EventParameters) {
            return $parameters->get();
        }
    }

    public function detach() {
        Event::detachById($this->getId());
    }

}
