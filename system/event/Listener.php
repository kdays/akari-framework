<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 08:54
 */

namespace Akari\system\event;

/**
 * Class Listener
 * 事件机制
 *
 * @package Akari\system\router
 */
Class Listener {

    /** @var EventHandler[][] $_listeners  */
    protected static $_listeners = [];

    protected static $_id = 0;

    /**
     * 添加监听器
     * <pre>
     * 添加事件的eventName允许使用*作为通配符，举例：*.*可以监听所有的事件；battle.*监听battle下的事件
     * 名称最多2层，即a.b
     *
     * 添加成功后，会返回事件id，通过事件id可以使用remove删除
     * 由于框架内部分事件也使用了Listener,如果做全局监听,务必做好区分.
     * </pre>
     *
     * @param string $eventName 事件名
     * @param callable $callback 回调见fire函数,永远返回Event对象,通过Event的params对象获得参数
     * @param int $priority 排序权重 数字越小的越先执行，全局性的
     * @return EventHandler
     */
    public static function add($eventName, callable $callback, $priority = 0) {
        list($gloSpace, $subSpace) = explode(".", $eventName);

        if (!isset(self::$_listeners[$gloSpace])) {
            self::$_listeners[$gloSpace] = [];
        }
        
        $event = new EventHandler($callback, $subSpace, ++self::$_id, $priority);
        self::$_listeners[$gloSpace][] = $event;
        
        return $event;
    }

    /**
     * @param $eventName
     * @return EventHandler[]
     */
    protected static function _getFireQueue($eventName) {
        list($gloSpace, $subSpace) = explode(".", $eventName);

        $fireQueue = [];
        $nowQueue = self::$_listeners;

        foreach ($nowQueue[$gloSpace] as $event) {
            if ($event->getType() == '*' || $event->getType() == $subSpace) {
                $fireQueue[] = $event;
            }
        }
        
        usort($fireQueue, function(EventHandler $a, EventHandler $b) {
            return $a->getPriority() > $b->getPriority() ? 1 : -1;
        });

        return $fireQueue;
    }

    /**
     * 事件唤起
     * 如果resultParameters有设置值时,每次事件如果有额外返回的也会被处理返回
     *
     * @param string $eventName
     * @param mixed $resultParameters
     * @return mixed
     */
    public static function fire($eventName, $resultParameters) {
        $queue = self::_getFireQueue($eventName);
        $event = new Event($eventName, $resultParameters);
        
        foreach ($queue as $listener) {
            $event->_setListener($listener);
            
            try {
                $event = $event->_tick();
            } catch (StopEventBubbling $e) {
                break;
            }
        }
        
        $result = $event->getData();
        unset($queue, $event);
        
        return $result;
    }

    public static function detach($eventType, $handler) {
        list($gEvent, $sEvent) = explode(".", $eventType);
        foreach (self::$_listeners[$gEvent] as $key => $listener) {
            if ($listener->getType() == $sEvent && $listener->getHandler() == $handler) {
                unset(self::$_listeners[$gEvent][$key]);
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    public static function detachById($id) {
        $queue = self::$_listeners;
        
        foreach ($queue as $eventType => $events) {
            foreach ($events as $sk => $event) {
                if ($event->getId() == $id) {
                    unset($queue[$eventType][$sk]);   
                    
                    self::$_listeners = $queue;
                    return TRUE;
                }
            }
        }
        
        return FALSE;
    }

    public static function detachAll($eventType) {
        unset(self::$_listeners[$eventType]);
    }
    
    public static function getListeners() {
        return self::$_listeners;
    }
    
}

