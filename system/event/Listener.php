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
 * Event Listener并不能改变结果，只是用来监听特定的数据变动进行处理
 * 实际的结果处理或根据URL处理，应使用Ruler而非Listener
 *
 * @package Akari\system\router
 */
Class Listener {

    protected static $queue = [];

    const EVENT_CALLBACK = 0;
    const EVENT_PRIORITY = 1;
    const EVENT_ID = 2;

    protected static $eventId = 0;

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
     * @return int 事件id
     */
    public static function add($eventName, callable $callback, $priority = 0) {
        list($gloSpace, $subSpace) = explode(".", $eventName);

        if (!isset(self::$queue[$gloSpace][$subSpace])) {
            self::$queue[$gloSpace][$subSpace] = [];
        }

        self::$queue[$gloSpace][$subSpace][] = [$callback, $priority, ++self::$eventId];
        return self::$eventId;
    }

    /**
     * 获得事件要执行的队列
     *
     * @param string $eventName 事件名
     * @return mixed
     */
    protected static function _getFireQueue($eventName) {
        list($gloSpace, $subSpace) = explode(".", $eventName);

        $fireQueue = [];
        $nowQueue = self::$queue;

        // 监听范围是这样的 event.specialName => event.* => *.specialName, *.*
        foreach ([$gloSpace, "*"] as $frontSpaceName) {
            foreach ([$subSpace, "*"] as $subSpaceName) {
                if (isset($nowQueue[$frontSpaceName][$subSpaceName])) {
                    $fireQueue = array_merge(array_reverse($nowQueue[$frontSpaceName][$subSpaceName]) ,$fireQueue);
                }
            }
        }

        // 排序
        usort($fireQueue, function($a, $b) {
            return $a[Listener::EVENT_PRIORITY] - $b[Listener::EVENT_PRIORITY];
        });

        return $fireQueue;
    }

    /**
     * 事件唤起
     *
     * @param string $eventName
     * @param string $resultParams
     * @return mixed
     */
    public static function fire($eventName, $resultParams) {
        $queue = self::_getFireQueue($eventName);

        $event = new Event();
        $event->params = $resultParams;
        $event->chainPos = 0;
        $event->eventName = $eventName;

        foreach ($queue as $nowEvent) {
            ++$event->chainPos;
            $event->eventId = $nowEvent[self::EVENT_ID];
            
            $returnValue = call_user_func($nowEvent[self::EVENT_CALLBACK], $event);
            if ($returnValue !== NULL) {
                // 如果有return 就判断如果返回Event就更新全部 不然就只更新result
                if ($returnValue instanceof Event) {
                    $event = $returnValue;
                } else {
                    $event->params = $returnValue;
                }
            }
            
            if ($event->isPropagationStopped()) {
                break;
            }
        }

        unset($queue);
        return $event->params;
    }

    /**
     * 删除事件，支持2种方式
     * <pre>
     * 1. eventId传入数字时，根据eventId删除
     * 2. 传入一个非数字型，只删除对应这个项目的事件列表
     * （举例：你传入battle.*，不会删除battle.buff之类的事件，只是删除battle.*下的事件）
     * </pre>
     *
     * @param string|int $eventId 事件id
     * @return bool
     */
    public static function remove($eventId) {
        if (!is_numeric($eventId)) {
            list($gloSpace, $subSpace) = explode(".", $eventId);
            if (isset(self::$queue[$gloSpace][$subSpace])) {
                unset(self::$queue[$gloSpace][$subSpace]);
                return TRUE;
            }

            return FALSE;
        }
        
        array_walk(self::$queue, function($queue, $gloSpace) use($eventId) {
            foreach ($queue as $subSpace => $events) {
                foreach ($events as $i => $nowEvent) {
                    if ($nowEvent[self::EVENT_ID] == $eventId) {
                        unset(self::$queue[$gloSpace][$subSpace][$i]);
                        return TRUE;
                    }
                }
            }
        });

        return FALSE;
    }

}

