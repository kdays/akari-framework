<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:05
 */

namespace Akari\system\db;

use Akari\system\event\Listener;
use Akari\system\event\StopEventBubbling;
use Akari\utility\Benchmark;
use Akari\utility\helper\Logging;
use \PDO;

Class DBAgent {

    use Logging;

    const EVT_DB_QUERY = "DB.Query";
    const EVT_DB_INIT = "DB.Init";

    /**
     * @var $pdo \PDO
     */
    protected $pdo;
    protected $options;
    protected $lastQuery;

    /**
     * @var null|int
     */
    public $lastInsertId = NULL;

    public function __construct(array $options) {
        $this->options = $options;
    }

    private function benchmarkEnd() {
        Benchmark::logCount('db.Query');
        Benchmark::logParams('db.Query', [
            'sql' => $this->lastQuery,
            'time' => Benchmark::getTimerDiff('db.Query')
        ]);
    }

    public function getPDOInstance() {
        if (!class_exists("PDO")) {
            throw new DBAgentException("PDO not installed");
        }

        if ($this->pdo === NULL) {
            Listener::fire(self::EVT_DB_INIT, ['opts' => $this->options]);
            extract($this->options);

            try {
                Listener::fire(self::EVT_DB_INIT, $this->options);
                $this->pdo = new PDO($dsn, $username, $password, $options);
            } catch (\PDOException $e) {
                self::_logErr($e);
                throw new DBAgentException("pdo connect failed: ". $e->getMessage());
            }
        }

        return $this->pdo;
    }

    public function prepare($sql) {
        return new DBAgentStatement($sql, $this);
    }

    /**
     * 获得所有符合条件的
     *
     * @param string|DBAgentStatement $sql
     * @param null|array $params
     * @return array
     */
    public function getAll($sql, $params = NULL) {
        $pdo = $this->getPDOInstance();
        $query = $this->doQuery($sql, $params);

        $doc = $query->getDoc();
        $result = $doc->execute();
        $rs = [];

        if ($result) {
            $rs = $doc->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $this->dispErrorInfo($doc->errorInfo());
        }

        $query->close();
        $this->benchmarkEnd();

        return $rs;
    }

    /**
     * @param DBAgentStatement $st
     * @param callable $callback
     * @return array
     * @throws DBAgentException
     */
    public function getAllWithCallback(DBAgentStatement $st, callable $callback) {
        $result = $this->getAll($st, NULL);

        foreach ($result as $key => &$value) {
            try {
                $value = $callback($value, $key);
            } catch (StopEventBubbling $e) {
                break;
            }
        }

        return $result;
    }

    /**
     * get one result
     *
     * @param string|DBAgentStatement $sql
     * @param null|array $params
     * @return array|mixed
     */
    public function getOne($sql, $params = NULL) {
        $pdo = $this->getPDOInstance();
        $query = $this->doQuery($sql, $params);

        $doc = $query->getDoc();
        $result = $doc->execute();
        $rs = [];

        if ($result) {
            $rs = $doc->fetch(PDO::FETCH_ASSOC);
        } else {
            $this->dispErrorInfo($doc->errorInfo());
        }

        $query->close();
        $this->benchmarkEnd();

        return $rs;
    }

    /**
     * execute sql
     *
     * @param string|DBAgentStatement $sql SQL语句
     * @param null|array $params
     * @return int
     */
    public function execute($sql, $params = NULL) {
        $pdo = $this->getPDOInstance();
        $query = $this->doQuery($sql, $params);

        $doc = $query->getDoc();
        $result = $doc->execute();

        if ($result) {
            $this->lastInsertId = $pdo->lastInsertId();
        } else {
            $this->dispErrorInfo($doc->errorInfo());
        }

        $query->close();
        $this->benchmarkEnd();

        return $doc->rowCount();
    }

    /**
     * 将字符型的SQL处理成预处理SQL
     *
     * @param string $sql
     * @param array $params
     * @return DBAgentStatement
     */
    private function _createStatementArr($sql, $params) {
        $st = $this->prepare($sql);
        if (is_array($params)) {
            foreach ($params as $key => $value) {
                $st->bindValue($key, $value);
            }
        }

        return $st;
    }

    /**
     * 处理错误信息
     *
     * @param array $errorArr 错误信息列 来自pdo->errorInfo()
     * @throws DBAgentException
     */
    private function dispErrorInfo($errorArr) {
        list($errorCode, , $errorMsg) = $errorArr;

        $expMsg = sprintf("Database Query Failed, message: %s (code: %s) ", $errorMsg, $errorCode);
        if (!empty($this->lastQuery)) {
            $expMsg .= "with SQL: ". $this->colorSql($this->lastQuery);
        }

        throw new DBAgentException($expMsg);
    }

    private function colorSql( $query ) {
        $query = preg_replace("/['\"]([^'\"]*)['\"]/i", "'<span style='text-decoration: underline;'>$1</span>'", $query, -1);

        return $query;
    }

    /**
     * 进行查询获得对象
     *
     * @param string $sql
     * @param null|array $params
     * @return DBAgentStatement
     */
    private function doQuery($sql, $params = NULL) {
        if (is_string($sql)) {
            $sql = $this->_createStatementArr($sql, $params);
        }

        Benchmark::setTimer('db.Query');
        $this->lastQuery = $sql->getDebugSQL();
        Listener::fire(self::EVT_DB_QUERY, ["SQL" => $this->lastQuery]);

        return $sql;
    }

    /**
     * 开始一个事务
     *
     * @return bool
     */
    public function beginTransaction() {
        return $this->getPDOInstance()->beginTransaction();
    }

    /**
     * 提交当前事务
     *
     * @return bool
     */
    public function commit() {
        return $this->getPDOInstance()->commit();
    }

    /**
     * 事务回滚
     *
     * @return bool
     */
    public function rollback() {
        return $this->getPDOInstance()->rollBack();
    }

    /**
     * 是否在事务状态
     *
     * @return bool
     * @throws DBAgentException
     */
    public function inTransaction() {
        return !!$this->getPDOInstance()->inTransaction();
    }

    /**
     * 获得当前数据库信息
     *
     * @return array
     */
    public function info() {
        $pdo = $this->getPDOInstance();
        $output = array(
            'server' => 'SERVER_INFO',
            'driver' => 'DRIVER_NAME',
            'client' => 'CLIENT_VERSION',
            'version' => 'SERVER_VERSION',
            'connection' => 'CONNECTION_STATUS'
        );

        foreach ($output as $key => $value) {
            $output[ $key ] = $pdo->getAttribute(constant('PDO::ATTR_' . $value));
        }

        return $output;
    }
}

Class DBAgentException extends \Exception {

}