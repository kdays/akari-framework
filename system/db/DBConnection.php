<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-01
 * Time: 18:20
 */

namespace Akari\system\db;

use Akari\Core;
use Akari\exception\DBException;
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

    /**
     * <b>这是一个底层方法</b>
     * 执行SQL
     *
     * @param string $sql
     * @param array $values
     * @param bool $returnLastInsertId 是否返回最近插入的ID
     * @return bool|int
     */
    public function query($sql, $values = [], $returnLastInsertId = FALSE) {
        $writeConn = $this->getConn()->getPdo();
        $st = $this->prepare($writeConn, $sql, $values);

        if ($st->execute()) {
            $result = $returnLastInsertId ? $writeConn->lastInsertId() : $st->rowCount();
            $this->closeCollection($st);

            return $result;
        }

        $this->_throwInternalErr($st, $sql);
    }

    /**
     * <b>这是一个底层方法</b>
     * 会调用PDO的fetchAll
     *
     * @param string $sql
     * @param array $values
     * @param int $fetchMode see \PDO::FETCH_*
     * @return array
     * @throws DBException
     */
    public function fetch($sql, $values = [], $fetchMode = \PDO::FETCH_ASSOC) {
        $conn = $this->getConn()->getReadPdo();
        $st = $this->prepare($conn, $sql, $values);

        if ($st->execute()) {
            $result = $st->fetchAll($fetchMode);
            $st->closeCursor();

            return $result;
        }

        $this->throwErr($st);
    }

    public function fetchOne($sql, $values = [], $fetchMode = \PDO::FETCH_ASSOC) {
        $conn = $this->getConn()->getReadPdo();
        $st = $this->prepare($conn, $sql, $values);
        if ($st->execute()) {
            $result = $st->fetch($fetchMode);
            $st->closeCursor();

            return $result;
        }

        $this->throwErr($st);
    }

    protected function prepare(\PDO $connection, string $sql, array $values) {
        $st = $connection->prepare($sql);
        foreach ($values as $key => $value) {
            $st->bindValue($key, $value);
        }

        return $st;
    }

    protected function _throwInternalErr(\PDOStatement $st, $madeSQL = NULL) {
        $errorInfo = $st->errorInfo();

        $ex = new DBException("Query Failed: " . $errorInfo[0] . " " . $errorInfo[2]);
        $ex->setQueryString($madeSQL ?? $st->queryString ?? '');
        throw $ex;
    }

}
