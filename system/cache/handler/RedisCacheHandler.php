<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/6/8
 * Time: 下午2:21.
 */

namespace Akari\system\cache\handler;

use Akari\system\cache\CacheBenchmark;

class RedisCacheHandler implements ICacheHandler
{
    /** @var \Redis */
    protected $redisHandler;
    protected $host;
    protected $port;
    protected $password;

    protected $isInTransaction = false;

    public function __construct(array $opts)
    {
        $redis = new \Redis();

        $host = array_key_exists('host', $opts) ? $opts['host'] : '127.0.0.1';
        $port = array_key_exists('port', $opts) ? $opts['port'] : 6379;

        $redis->connect($host, $port);

        $password = array_key_exists('password', $opts) ? $opts['password'] : false;
        if ($password) {
            $redis->auth($password);
        }

        $this->redisHandler = $redis;
        $this->host = $host;
        $this->port = $port;
        $this->password = $password;
    }

    /**
     * 设置缓存.
     *
     * @param string   $key     键名
     * @param mixed    $value   值
     * @param null|int $timeout 超时时间
     *
     * @return bool
     */
    public function set($key, $value, $timeout = null)
    {
        $value = serialize($value);

        return $this->redisHandler->set($key, $value, $timeout);
    }

    /**
     * 获得缓存设置的键.
     *
     * @param string     $key
     * @param null|mixed $defaultValue
     *
     * @return mixed
     */
    public function get($key, $defaultValue = null)
    {
        if (!$this->exists($key)) {
            CacheBenchmark::log(CacheBenchmark::MISS);

            return $defaultValue;
        }

        CacheBenchmark::log(CacheBenchmark::HIT);
        $value = $this->redisHandler->get($key);

        return unserialize($value);
    }

    /**
     * 删除某个缓存键.
     *
     * @param string $key
     *
     * @return bool
     */
    public function remove($key)
    {
        if (!$this->exists($key)) {
            return false;
        }

        $this->redisHandler->delete($key);

        return true;
    }

    /**
     * 检查某个缓存键是否存在.
     *
     * @param $key
     *
     * @return bool
     */
    public function exists($key)
    {
        return $this->redisHandler->exists($key);
    }

    /**
     * 获得所有的缓存键.
     *
     * @return array
     */
    public function all()
    {
        return $this->redisHandler->keys('*');
    }

    /**
     * 开启缓存事务，直到commit才会递交.
     *
     * 如果你一时要对大量缓存进行变更时，可以减少对于缓存索引的压力
     *
     * 请注意 和数据库不同，文件缓存的模拟事务并不会在commit前执行
     *
     * @return bool
     */
    public function startTransaction()
    {
        if ($this->isInTransaction) {
            return false;
        }

        $this->isInTransaction = true;
        $this->redisHandler->multi();
    }

    /**
     * 是否处于事务状态中.
     *
     * @return bool
     */
    public function inTransaction()
    {
        return (bool) $this->isInTransaction;
    }

    /**
     * 提交一个事务
     *
     * 成功返回True，没有处于事务或没有任何更新提交，返回False
     *
     * @return bool
     */
    public function commit()
    {
        if (!$this->isInTransaction) {
            return false;
        }
        $this->isInTransaction = false;
        $this->redisHandler->exec();

        return true;
    }

    /**
     * 将目前处于事务状态的内容撤销提交.
     *
     * @return bool
     */
    public function rollback()
    {
        $this->isInTransaction = false;
        $this->redisHandler->discard();

        return true;
    }

    /**
     * 获得原生缓存对象，如Redis返回Redis对象
     *
     * 如文件没有的则会抛出错误
     *
     * @return mixed
     */
    public function getHandler()
    {
        return $this->redisHandler;
    }

    public function flush()
    {
        return $this->redisHandler->flushDB();
    }

    public function increment($key, $value = 1)
    {
        return $this->redisHandler->incrBy($key, $value);
    }

    public function decrement($key, $value = 1)
    {
        return $this->redisHandler->decrBy($key, $value);
    }

    /**
     * 是否支持事务
     *
     * @return bool
     */
    public function isSupportTransaction()
    {
        return true;
    }
}
