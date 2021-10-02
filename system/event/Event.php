<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/24
 * Time: 1:30
 */

namespace Akari\system\event;

class Event {

    protected static $listeners = [];
    protected static $eventCounter = [];
    protected static $eventId = 0;

    public static $debug = FALSE;

    public static function register(string $event, callable $callback, $priority = 0) {
        list($gloEvent, $subEvent) = explode(".", $event);

        if (!isset(self::$listeners[$gloEvent])) {
            self::$listeners[$gloEvent] = [];
        }

        $handler = new EventListener($callback, $event, $priority, ++self::$eventId);
        if (self::$debug) {
            $stacks = debug_backtrace();
            $trace = array_shift($stacks);
            $handler->setDebug($trace['file'], $trace['line']);
        }

        self::$listeners[$gloEvent][] = $handler;
    }

    public static function detach(string $eventType, ?callable $callback) {
        list($gloEvent, $subEvent) = explode(".", $eventType);

        if ($callback === NULL) {
            self::$listeners[$gloEvent] = [];
        } else {
            foreach (self::getListeners($eventType) as $key => $listener) {
                if ($listener->getHandler() == $callback) {
                    unset(self::$listeners[$gloEvent][$key]);
                    $listener = NULL;
                }
            }
        }
    }

    public static function detachById(int $eventId) {
        foreach (self::$listeners as $prefix => $listeners) {
            foreach ($listeners as $k => $listener) {
                if ($listener->getId() == $eventId) {
                    unset(self::$listeners[$prefix][$k]);
                }
            }
        }
    }

    public static function fire(string $eventType, $parameters) {
        if (empty(self::$eventCounter[$eventType])) {
            self::$eventCounter[$eventType] = 0;
        }

        ++self::$eventCounter[$eventType];

        /** @var EventListener $listener */
        foreach (self::getListeners($eventType, TRUE) as $listener) {
            $listener->fire($parameters);
        }

        if ($parameters instanceof EventParameters) {
            return $parameters->get();
        }
    }

    public static function getFiredCount(string $eventType) {
        return self::$eventCounter[$eventType] ?? 0;
    }

    /**
     * @param string|null $eventType
     * @param bool $includeSubAll
     * @return EventListener[]
     */
    public static function getListeners(?string $eventType, $includeSubAll = FALSE) {
        if (empty($eventType)) return self::$listeners;

        list($gloEvent, $subEvent) = explode(".", $eventType);
        if (empty(self::$listeners[$gloEvent])) return [];

        $fireQueue = [];

        /** @var EventListener $listener */
        foreach (self::$listeners[$gloEvent] as $listener) {
            if ($listener->getType() == $eventType ||
                ($includeSubAll && $listener->getType() == $gloEvent . ".*")) {
                $fireQueue[] = $listener;
            }
        }

        usort($fireQueue, function (EventListener $a, EventListener $b) {
            return $a->getPriority() > $b->getPriority() ? 1 : -1;
        });

        return $fireQueue;
    }

}
