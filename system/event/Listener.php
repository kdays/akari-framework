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
     *
     * 注意监听时，fire的选择！
     * 系统全局的请使用fire类型，战斗系统严重依赖返回值的，使用fireSync
     * 两者回调时参数不同，请注意区分。
     * </pre>
     *
     * @param string $eventName 事件名
     * @param callable $callback 回调的参数见fire和fireSync，2者回调内容不同
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
     * 异步事件调用
     * <pre>
     * 和fireSync不同，2者callback返回不一致，
     * fire是array $params, string $eventName, int $eventId
     * </pre>
     *
     * @param string $eventName
     * @param array $params
     */
    public static function fire($eventName, array $params) {
        $queue = self::_getFireQueue($eventName);

        foreach ($queue as $_) {
            try {
                call_user_func_array($_[self::EVENT_CALLBACK], [
                            $params,
                            $eventName,
                            $_[self::EVENT_ID]
                        ]);
            } catch(StopEventBubbling $e) {
                break;
            }
        }
    }

    /**
     * 链式事件调用
     * <pre>
     * 和fire不同，2者callback返回不一致
     * fireSync回调callback是Event $e
     * </pre>
     *
     * @param string $eventName
     * @param mixed $inResult
     * @return mixed
     */
    public static function fireSync($eventName, $inResult) {
        $queue = self::_getFireQueue($eventName);

        $event = new Event();
        $event->result = $inResult;
        $event->chainPos = 0;
        $event->eventName = $eventName;

        foreach ($queue as $_) {
            ++$event->chainPos;
            $event->eventId = $_[self::EVENT_ID];

            $event->result = call_user_func($_[self::EVENT_CALLBACK], $event);
            if ($event->isPropagationStopped()) {
                break;
            }
        }

        unset($queue);
        return $event->result;
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

