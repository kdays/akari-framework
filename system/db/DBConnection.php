<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-01
 * Time: 18:20
 */

namespace Akari\system\db;

use Akari\Core;
use Illuminate\Database\Capsule\Manager as Capsule;

class DBConnection {

    protected $options;
    protected $inTrans = FALSE;

    /** @var Capsule */
    protected static $capsule;
    protected $id;

    protected static $instances = [];

    /**
     * @param string $config
     * @return DBConnection
     */
    public static function init(string $config = 'default') {
        if (!isset(self::$instances[$config])) {
            self::$instances[$config] = new self( $config, Core::env('database')[$config] );;
        }

        return self::$instances[$config];
    }

    public function __construct(string $id, array $options) {
        $this->options = $options;
        $this->id = $id;

        $this->connect($options);
    }

    public function connect(array $options) {
        $isInit = empty(self::$capsule);
        $capsule = self::$capsule ?? new Capsule();

        $capsule->addConnection([
            'driver' => $options['type'] ?? 'mysql',
            'host' => $options['host'] . ($options['port'] ? ':' . $options['port'] : ''),
            'database' => $options['database'],
            'username' => $options['username'],
            'password' => $options['password'],
            'charset' => 'utf8mb4'
        ], $this->id);

        if ($isInit) {
            $capsule->setAsGlobal();
            $capsule->bootEloquent();

            self::$capsule = $capsule;
        }
    }

    /**
     * @return \Illuminate\Database\Connection
     */
    public function getConn() {
        return self::$capsule->getConnection($this->id);
    }

    /**
     * 开始一个事务
     *
     * @return bool
     */
    public function beginTransaction() {
        $this->inTrans = TRUE;

        return $this->getConn()->beginTransaction();
    }

    /**
     * 提交当前事务
     *
     * @return bool
     */
    public function commit() {
        $this->inTrans = FALSE;

        return $this->getConn()->commit();
    }

    /**
     * 事务回滚
     *
     * @return bool
     */
    public function rollback() {
        $this->inTrans = FALSE;

        return $this->getConn()->rollBack();
    }

    /**
     * 是否在事务状态
     *
     * @return bool
     */
    public function inTransaction() {
        return $this->getConn()->transactionLevel() > 0;
    }

    public function getDbType() {
        return strtolower($this->options['type']);
    }

    public function getDbName() {
        return $this->options['database'];
    }

    /**
     * @return \Illuminate\Database\Schema\Builder
     */
    public function migration() {
        return $this->getConn()->getSchemaBuilder();
    }

}
