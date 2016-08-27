<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/29
 * Time: 08:54
 */

namespace Akari\system\event;
use Akari\system\exception\AkariException;

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
     * 添加事件的eventName允许使用*作为通配符，举例：battle.*监听battle下的事件
     * 名称最多2层，即a.b 不支持类似*.battle这样的写法
     * </pre>
     *
     * @param string $eventName 事件名
     * @param mixed $callback 回调见fire函数 调用参数为(Event $event)
     * <pre>
     * $callback支持2种方式 一种是匿名函数方式(callable)
     * 另外种传入一个类,调用时会检查:
     *
     * 举例事件调用是Processor.sent 那么会尝试调用类中的2个方法: sentAction 和 handle
     * </pre>
     *
     * @param int $priority 排序权重 数字越小的越先执行
     * @return EventHandler
     * @throws AkariException
     */
    public static function add($eventName, callable $callback, $priority = 0) {
        $r = explode(".", $eventName);
        if (count($r) < 2)  $r = ['UKN', $eventName];
        
        list($gloSpace, $subSpace) = $r;
        
        if ($gloSpace == '*') {
            throw new AkariException("Event global space can not be *");
        }

        if (!isset(self::$_listeners[$gloSpace])) {
            self::$_listeners[$gloSpace] = [];
        }
        
        $event = new EventHandler($callback, $subSpace, ++self::$_id, $priority);
        self::$_listeners[$gloSpace][] = $event;
        
        return $event;
    }

    /**
     * @param string $eventName
     * @return EventHandler[]
     */
    protected static function _getFireQueue($eventName) {
        $r = explode(".", $eventName);
        if (count($r) < 2)  $r = ['UKN', $eventName];

        list($gloSpace, $subSpace) = $r;

        $fireQueue = [];
        $nowQueue = self::$_listeners;
        
        if (!isset($nowQueue[$gloSpace])) {
            return [];
        }

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

    /**
     * 撤销某个事件监听 和add方法一致
     * 
     * @param string $eventType
     * @param mixed $handler
     * @return bool
     */
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

    /**
     * 根据EventHandler中的id撤销事件监听
     * 
     * @param int $id
     * @return bool
     */
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

    /**
     * 撤销某个事件下的所有监听
     * 
     * @param string $eventType
     */
    public static function detachAll($eventType) {
        unset(self::$_listeners[$eventType]);
    }

    /**
     * 返回所有监听器
     * 
     * @return EventHandler[][]
     */
    public static function getListeners() {
        return self::$_listeners;
    }
    
}
