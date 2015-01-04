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

    /**
     * @param string $eventName
     * @param callable $callback
     * @param int $weight
     * @return bool
     */
    public static function add($eventName, callable $callback, $weight = 0) {
        list($gloSpace, $subSpace) = explode(".", $eventName);

        if (isset(self::$queue[$gloSpace][$subSpace])) {
            foreach (self::$queue[$gloSpace][$subSpace] as $value) {
                if ($value[self::EVENT_CALLBACK] == $callback) {
                    return FALSE;
                }
            }
        }

        self::$queue[$gloSpace][$subSpace][] = [$callback, $weight];
    }

    /**
     *
     *
     * @param $eventName
     * @param array $params
     */
    public static function fire($eventName, array $params = []) {
        list($gloSpace, $subSpace) = explode(".", $eventName);

        $fireQueue = [];
        $nowQueue = self::$queue;

        // 监听范围是这样的 event.specialName => event.* => *.specialName, *.*
        foreach ([$gloSpace, "*"] as $frontSpaceName) {
            foreach ([$subSpace, "*"] as $subSpaceName) {
                if (isset($nowQueue[$frontSpaceName][$subSpaceName])) {
                    $fireQueue = array_merge($fireQueue, $nowQueue[$frontSpaceName][$subSpaceName]);
                }
            }
        }

        // 排序
        usort($fireQueue, function($a, $b) {
            return $a[Listener::EVENT_WEIGHT] - $b[Listener::EVENT_WEIGHT];
        });

        foreach ($fireQueue as $nowEvent) {
            if ($nowEvent[self::EVENT_CALLBACK]($params) === FALSE) {
                break;
            }
        }
    }

}