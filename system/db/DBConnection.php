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
use Akari\system\util\Collection;

class DBConnection {

    protected $options;

    protected $readConnection;
    protected $writeConnection;

    protected static $instances = [];

    public static function init(string $config = 'default') {
        if (!isset(self::$instances[$config])) {
            self::$instances[$config] = new self( Core::env('database')[$config] );
        }

        return self::$instances[$config];
    }

    public function __construct(array $options) {
        $this->options = $options;
    }

    public function connect(array $options) {
        if (!extension_loaded("pdo")) {
            throw new DBException("need PDO");
        }

        try {
            if (!empty($options['dsn'])) {
                $dsn = $options['dsn'];
            } else {
                $dsn = $options['type'] . ":host=" . $options['host'] . ";port=" . $options['port'] . ";dbname=" . $options['database'];
            }

            return new \PDO($dsn, $options['username'], $options['password'], $options['options']);
        } catch (\PDOException $e) {
            throw new DBException("Connect Failed: " . $e->getMessage());
        }

    }

    public function getReadConnection() {
        if (empty($this->readConnection)) {
            $options = $this->options;

            if (array_key_exists("slaves", $options)) {
                $this->readConnection = $this->connect( $options['slaves'][array_rand($options['slaves'])] );
            } else {
                $this->readConnection = $this->connect( $options );
            }
        }

        return $this->readConnection;
    }

    public function getWriteConnection() {
        if (!$this->writeConnection) {
            $opts = $this->options;
            $this->writeConnection = $this->connect($opts);
        }

        return $this->writeConnection;
    }

    /**
     * 开始一个事务
     *
     * @return bool
     */
    public function beginTransaction() {
        return $this->getWriteConnection()->beginTransaction();
    }

    /**
     * 提交当前事务
     *
     * @return bool
     */
    public function commit() {
        return $this->getWriteConnection()->commit();
    }

    /**
     * 事务回滚
     *
     * @return bool
     */
    public function rollback() {
        return $this->getWriteConnection()->rollBack();
    }

    /**
     * 是否在事务状态
     *
     * @return bool
     */
    public function inTransaction() {
        return !!$this->getWriteConnection()->inTransaction();
    }

    protected function closeCollection(\PDOStatement $st) {
        $st->closeCursor();
    }

    protected function throwErr(\PDOStatement $st, $madeSQL = NULL) {
        $errorInfo = $st->errorInfo();

        $ex = new DBException("Query Failed: " . $errorInfo[0] . " " . $errorInfo[2]);
        $ex->setQueryString($madeSQL ?? $st->queryString ?? '');
        throw $ex;
    }

    protected function prepare(\PDO $connection, string $sql, array $values) {
        $st = $connection->prepare($sql);
        foreach ($values as $key => $value) {
            $st->bindValue($key, $value);
        }

        return $st;
    }

    public function getDbType() {
        if (!empty($this->options['dsn'])) {
            list($type, $_options) = explode(":", $this->options['dsn']);

            return strtolower($type);
        }

        return strtolower($this->options['type']);
    }

    public function getDbName() {
        if (!empty($this->options['dsn'])) {
            list($type, $_options) = explode(":", $this->options['dsn']);
            $options = [];

            foreach (explode(";", $_options) as $v) {
                parse_str($v, $v);
                $options = array_merge($options, $v);
            }

            return $options['dbname'];
        }

        return $this->options['database'];
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
        $writeConn = $this->getWriteConnection();
        $st = $this->prepare($writeConn, $sql, $values);

        if ($st->execute()) {
            $result = $returnLastInsertId ? $writeConn->lastInsertId() : $st->rowCount();
            $this->closeCollection($st);

            return $result;
        }

        $this->throwErr($st, $sql);
    }

    /**
     * <b>这是一个底层方法</b>
     * 会调用PDO的fetchAll
     *
     * @param string $sql
     * @param array $values
     * @param int $fetchMode see \PDO::FETCH_*
     * @return Collection
     * @throws DBException
     */
    public function fetch($sql, $values = [], $fetchMode = \PDO::FETCH_ASSOC) {
        $st = $this->prepare($this->getReadConnection(), $sql, $values);

        if ($st->execute()) {
            $result = $st->fetchAll($fetchMode);
            $this->closeCollection($st);

            return Collection::make($result);
        }

        $this->throwErr($st);
    }

    public function fetchOne($sql, $values = [], $fetchMode = \PDO::FETCH_ASSOC) {
        $st = $this->prepare($this->getReadConnection(), $sql, $values);
        if ($st->execute()) {
            $result = $st->fetch($fetchMode);
            $this->closeCollection($st);

            return $result;
        }

        $this->throwErr($st);
    }

    /**
     * <b>这是一个底层方法</b>
     * 快速查询一列中的一个值
     *
     * @param string $sql
     * @param array $values
     * @param int $columnIdx 返回查询返回的第几个值
     * @return bool|string
     * @throws DBException
     */
    public function fetchValue($sql, $values = [], $columnIdx = 0) {
        $st = $this->prepare($this->getReadConnection(), $sql, $values);
        if ($st->execute()) {
            $result = $st->fetchColumn($columnIdx);
            $this->closeCollection($st);

            return $result;
        }

        $this->throwErr($st);
    }

    public function migration() {
        return new DBMigration($this);
    }

}
