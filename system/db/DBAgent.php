<?php
namespace Akari\system\db;

use Akari\system\Event;
use Akari\system\log\Logging;
use Akari\utility\BenchmarkHelper;
use \PDO;

Class DBAgent {

    public $doBenchmark = FALSE;

    const EVENT_DB_QUERY = "DBAgent.Query";
    const EVENT_DB_INIT = "DBAgent.Init";

    /**
     * 性能记录
     *
     * @param string $action 行为start or end
     */
    public function logBenchmark($action = 'start') {
        if (!$this->doBenchmark)    return ;

        $trace = debug_backtrace();
        $backtrace = [];

        foreach ($trace as $value) {
            $backtrace[] = basename($value['file'])." L".$value['line'];
        }

        BenchmarkHelper::setSQLTimer($this->lastQuery, $action, $backtrace, []);
    }

    /**
     * @var $pdo \PDO
     */
    protected $pdo = NULL;
    protected $options;
    protected $lastQuery;
    public $lastInsertId = NULL;

    public function __construct($options) {
        $this->options = $options;
    }

    /**
     * 获得PDO实例
     *
     * @return PDO
     * @throws DBAgentException
     */
    public function getPDOInstance() {
        if (!class_exists("PDO")) {
            throw new DBAgentException("[Akari.db.DBAgent] PDO not installed");
        }

        if ($this->pdo === NULL) {
            extract($this->options);

            try {
                Event::fire(self::EVENT_DB_INIT);
                $this->pdo = new PDO($dsn, $username, $password, $options);
            } catch (\PDOException $e) {
                Logging::_logErr($e);
                throw new DBAgentException("PDO Connect Failed: ".$e->getCode(). " ".$e->getMessage());
            }
        }

        return $this->pdo;
    }

    /**
     * quoute
     *
     * @param string $string
     * @return string
     */
    public function quote($string) {
        return $this->pdo->quote($string);
    }

    /**
     * prepare
     *
     * @param string $sql
     * @return DBAgentStatement
     */
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
        $this->logBenchmark('end');

        $query->close();
        return $rs;
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
        $this->logBenchmark('end');

        $query->close();
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
        $this->logBenchmark('end');

        $query->close();
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

        throw new DBAgentException("[Akari.DBAgent] Query Error: $errorMsg ($errorCode) With SQL: ". $this->lastQuery);
    }

    /**
     * 进行查询获得对象
     *
     * @param string $sql
     * @param null|array $params
     * @return DBAgentStatement
     */
    private function doQuery($sql, $params = NULL) {
        logcount("db.query", 1);

        if (is_string($sql)) {
            $sql = $this->_createStatementArr($sql, $params);
        }

        $this->lastQuery = $sql->getDebugSQL();
        $this->logBenchmark('start');

        Event::fire(self::EVENT_DB_QUERY, ["SQL" => $this->lastQuery]);

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

Class DBAgentException extends \Exception{

}