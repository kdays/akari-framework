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

    /** @var  \PDO $writeConnection */
    protected $writeConnection;
    
    /** @var  \PDO $readConnection */
    protected $readConnection;
    
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

    protected function getPDOInstance($dsn, $username, $password, $options = []) {
        if (!class_exists("PDO")) {
            throw new DBAgentException("PDO Extension not installed!");
        }

        Listener::fire(self::EVT_DB_INIT, $this);

        try {
            Listener::fire(self::EVT_DB_INIT, $this->options);
            $connection = new PDO($dsn, $username, $password, $options);
        } catch (\PDOException $e) {
            self::_logErr($e);
            throw new DBAgentException("pdo connect failed: ". $e->getMessage());
        }
        
        return $connection;
    }

    /**
     * @param $sql
     * @return DBAgentStatement
     */
    public function prepare($sql) {
        return new DBAgentStatement($sql, $this->getReadConnection());
    }

    /**
     * 获得所有符合条件的
     *
     * @param string|DBAgentStatement $sql
     * @param null|array $params
     * @return array
     */
    public function getAll($sql, $params = NULL) {
        $pdo = $this->getReadConnection();
        $query = $this->getQuery($sql, $params);

        $doc = $query->getDoc($pdo);
        $result = $doc->execute();
        if (!$result) {
            $this->dispErrorInfo($doc->errorInfo());
        }

        $rs = $doc->fetchAll(PDO::FETCH_ASSOC);

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
     * @param int $resultType
     * @return array|mixed
     * @throws DBAgentException
     */
    public function getOne($sql, $params = NULL, $resultType = PDO::FETCH_ASSOC) {
        $pdo = $this->getReadConnection();
        $query = $this->getQuery($sql, $params);

        $doc = $query->getDoc($pdo);
        $result = $doc->execute();

        if (!$result) {
            $this->dispErrorInfo($doc->errorInfo());
        } 

        $rs = $doc->fetch($resultType);

        $query->close();
        $this->benchmarkEnd();

        return $rs;
    }

    /**
     * get one result of value
     * 
     * @param string|DBAgentStatement $sql
     * @param null|array $params
     * @param mixed $defaultValue
     * @return bool
     */
    public function getValue($sql, $params = NULL, $defaultValue = false) {
        $result = $this->getOne($sql, $params, PDO::FETCH_NUM);
        $field = 0;
        
        return isset($result[$field]) ? $result[$field] : $defaultValue;
    }
    

    /**
     * execute sql
     *
     * @param string|DBAgentStatement $sql SQL语句
     * @param null|array $params
     * @return int
     */
    public function execute($sql, $params = NULL) {
        $pdo = $this->getWriteConnection();
        $query = $this->getQuery($sql, $params);

        $doc = $query->getDoc($pdo);
        $result = $doc->execute();

        if (!$result) {
            $this->dispErrorInfo($doc->errorInfo());
        }

        $this->lastInsertId = $pdo->lastInsertId();
        
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
     * 进行查询获得对象
     *
     * @param string $sql
     * @param null|array $params
     * @return DBAgentStatement
     */
    private function getQuery($sql, $params = NULL) {
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
     * @throws DBAgentException
     */
    public function inTransaction() {
        return !!$this->getWriteConnection()->inTransaction();
    }

    /**
     * 获得当前数据库信息
     *
     * @return array
     */
    public function info() {
        $pdo = $this->getReadConnection();
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
    
    public function getWriteConnection() {
        if (!$this->writeConnection) {
            $opts = $this->options;
            $this->writeConnection = $this->getPDOInstance($opts['dsn'], $opts['username'], $opts['password'], $opts['options']);
        }
        
        return $this->writeConnection;
    }
    
    public function getReadConnection() {
        if (!$this->readConnection) {
            $opts = $this->options;
            
            // 主从分离存在从机时优先选择从机
            if (array_key_exists("slaves", $opts)) {
                $opts = $this->options['slaves'][ array_rand($opts['slaves']) ];
            }
            
            // 从机选择完毕 链接
            $this->readConnection = $this->getPDOInstance($opts['dsn'], $opts['username'], $opts['password'], $opts['options']);
        }
        
        return $this->readConnection;
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
        if (CLI_MODE) {
            return $query;
        }
        $query = preg_replace("/['\"]([^'\"]*)['\"]/i", "'<u>$1</u>'", $query, -1);

        return $query;
    }

}

Class DBAgentException extends \Exception {

}