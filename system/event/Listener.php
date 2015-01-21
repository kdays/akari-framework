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
    const EVENT_WEIGHT = 1;
    const EVENT_PARAMS = 2;
    const EVENT_ID = 3;

    protected static $eventId = 0;

    /**
     * 添加监听器
     * <pre>
     * 添加事件的eventName允许使用*作为通配符，举例：*.*可以监听所有的事件；battle.*监听battle下的事件
     * 名称最多2层，即a.b
     *
     * 添加成功后，会返回事件id，通过事件id可以使用remove删除
     * </pre>
     *
     * @param string $eventName
     * @param callable $callback 回调时2个参数 第1个是params 第2个是请求事件名
     * @param array $params
     * @param int $weight
     * @return int 事件id
     */
    public static function add($eventName, callable $callback, $params = [], $weight = 0) {
        list($gloSpace, $subSpace) = explode(".", $eventName);

        if (!isset(self::$queue[$gloSpace][$subSpace])) {
            self::$queue[$gloSpace][$subSpace] = [];
        }

        self::$queue[$gloSpace][$subSpace][] = [$callback, $weight, $params, ++self::$eventId];
        return self::$eventId;
    }

    /**
     * 执行事件
     *
     * @param string $eventName
     * @param array $params
     */
    public static function fire($eventName, $params = []) {
        list($gloSpace, $subSpace) = explode(".", $eventName);

        $fireQueue = [];
        $nowQueue = self::$queue;

        // 监听范围是这样的 event.specialName => event.* => *.specialName, *.*
        foreach ([$gloSpace, "*"] as $frontSpaceName) {
            foreach ([$subSpace, "*"] as $subSpaceName) {
                if (isset($nowQueue[$frontSpaceName][$subSpaceName])) {
                    $fireQueue = array_merge($nowQueue[$frontSpaceName][$subSpaceName], $fireQueue);
                }
            }
        }

        // 排序
        usort($fireQueue, function($a, $b) {
            return $a[Listener::EVENT_WEIGHT] - $b[Listener::EVENT_WEIGHT];
        });

        foreach ($fireQueue as $_nowREvent) {
            $sParams = array_merge($params, $_nowREvent[self::EVENT_PARAMS]);

            try {
                $_nowREvent[self::EVENT_CALLBACK]($sParams, $eventName);
            } catch (StopEventBubbling $e) {
                break;
            }
        }

        unset($fireQueue);
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
                for ($i = 0; $i < count($events); $i++) {
                    $nowEventId = $events[$i][self::EVENT_ID];
                    if ($nowEventId == $eventId) {
                        unset(self::$queue[$gloSpace][$subSpace][$i]);
                        return TRUE;
                    }
                }
            }
        });

        return FALSE;
    }

}
