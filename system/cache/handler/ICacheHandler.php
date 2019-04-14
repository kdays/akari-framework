<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-25
 * Time: 17:59
 */

namespace Akari\system\cache\handler;

interface ICacheHandler {

    /**
     * 设置缓存
     *
     * @param string $key 键名
     * @param mixed $value 值
     * @param null|int $timeout 超时时间
     * @param bool $raw
     * @return boolean
     */
    public function set($key, $value, $timeout = NULL, $raw = FALSE);

    /**
     * 获得缓存设置的键
     *
     * @param string $key
     * @param null|mixed $defaultValue
     * @param bool $raw
     * @return mixed
     */
    public function get($key, $defaultValue = NULL, $raw = FALSE);

    /**
     * 删除某个缓存键
     *
     * @param string $key
     * @return boolean
     */
    public function remove($key);

    /**
     * 检查某个缓存键是否存在
     *
     * @param $key
     * @return boolean
     */
    public function exists($key);

    /**
     * 获得所有的缓存键
     *
     * @return array
     */
    public function all();

    /**
     * 清空数据库
     * @return mixed
     */
    public function flush();

    /**
     * 原子计数，加
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function increment($key, $value = 1);

    /**
     * 原子计数，减
     *
     * @param string $key
     * @param int $value
     * @return int
     */
    public function decrement($key, $value = 1);

    /**
     * 是否支持事务
     *
     * @return bool
     */
    public function isSupportTransaction();

    /**
     * 开启缓存事务，直到commit才会递交
     *
     * 如果你一时要对大量缓存进行变更时，可以减少对于缓存索引的压力
     *
     * 请注意 和数据库不同，文件缓存的模拟事务并不会在commit前执行
     *
     * @return boolean
     */
    public function startTransaction();

    /**
     * 是否处于事务状态中
     *
     * @return boolean
     */
    public function inTransaction();

    /**
     * 提交一个事务
     *
     * 成功返回True，没有处于事务或没有任何更新提交，返回False
     *
     * @return boolean
     */
    public function commit();

    /**
     * 将目前处于事务状态的内容撤销提交
     *
     * @return boolean
     */
    public function rollback();

    /**
     * 获得原生缓存对象，如Redis返回Redis对象
     *
     * 如文件没有的则会抛出错误
     *
     * @return mixed
     */
    public function getHandler();

}
