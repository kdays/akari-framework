<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/30
 * Time: 下午6:26
 */

namespace Akari\system\conn;


use Akari\utility\Benchmark;
use PDO;

class SQLMapBuilder {
    
    private $map;
    private $connection;
    
    const TYPE_SELECT = 'SELECT';
    const TYPE_DELETE = 'DELETE';
    const TYPE_UPDATE = 'UPDATE';
    const TYPE_INSERT = 'INSERT';
    const TYPE_COUNT = 'COUNT';
    const TYPE_ROW = 'ROW';
    const TYPE_RAW = 'RAW'; // 特殊
    
    const BENCHMARK_KEY = "db.Query";
    
    public function __construct(BaseSQLMap $SQLMap, DBConnection $connection) {
        $this->map = $SQLMap;
        $this->connection = $connection;
    }

    /**
     * @param $id
     * @param array $data
     * @return mixed
     * @throws DBException
     */
    public function execute($id, array $data) {
        $lists = $this->map->lists;
        if (!isset($lists[$id])) {
            throw new DBException("SQLMap mismatch event: $id");
        }
        
        $item = $lists[$id];
        $type = strtoupper(explode(".", $id)[0]);

        Benchmark::setTimer(self::BENCHMARK_KEY);
        
        list($item, $data) = $this->prepareDataForSql($item, $data);
        if ($type == self::TYPE_COUNT || $type == self::TYPE_SELECT || $type == self::TYPE_ROW) {
            $connection = $this->connection->getReadConnection();
        } else {
            $connection = $this->connection->getWriteConnection();
        }
        
        $stmt = $connection->prepare($item['sql']);
        foreach ($data as $k => $v) {
            if ($k[0] == '@')   continue;
            $stmt->bindValue($k, $v);
        }
        
        foreach ($this->_values as $k => $v) {
            $stmt->bindValue("AB_". $k, $v);
        }
        
        
        if (!$stmt->execute()) {
            $errInfo = $stmt->errorInfo();
            throw new DBException("SQL Exec [". $id. "] Failed, Return ". $errInfo[0]. " ". $errInfo[2]);
        }
        
        $result = NULL;
        if ($type == self::TYPE_COUNT) {
            $result = $stmt->fetch(PDO::FETCH_NUM)[0];
        } elseif ($type == self::TYPE_SELECT) {
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif ($type == self::TYPE_ROW) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } elseif ($type == self::TYPE_INSERT) {
            if ($stmt->rowCount() > 0) {
                $result = $connection->lastInsertId();
            }
        } else {
            $result = $stmt->rowCount();
        }

        $this->close($stmt);
        
        Benchmark::logCount(self::BENCHMARK_KEY);
        Benchmark::logParams(self::BENCHMARK_KEY, [
            'sql' => sprintf("%s -> %s (%s)", get_class($this->map), $id, $item['sql']), 
            'time' => Benchmark::getTimerDiff(self::BENCHMARK_KEY)
        ]);
        
        return $result;
    }
    
    public function close(\PDOStatement $stmt) {
        $stmt->closeCursor();
        $this->_values = [];
        $this->_bindCount = 0;
    }
    
    private function prepareDataForSql($item, $data) {
        $sql = $item['sql'];
        
        if (isset($item['required'])) {
            foreach ($item['required'] as $key) {
                if (!isset($data[$key]) and !isset($data['@vars'][$key])) {
                    throw new DBException("Sql: $sql required $key");
                }
            }
        }
        
        if (isset($data['@limit'])) {
            $ll = $data['@limit'];
            $sql = str_ireplace("#limit", (is_array($ll) ? ' LIMIT '. $ll[0]. ",". $ll[1] : ' LIMIT '.$ll), $sql);
        } 
        
        if (isset($data['@keys'])) {
            $rr = [];
            foreach ($data['@keys'] as $key) {
                $rr[] = " $key = :$key";
            }
            
            $sql = str_ireplace("#keys", implode(",", $rr), $sql);
        }
        
        if (isset($data['@sort'])) {
            $sql = str_ireplace("#sort", " ORDER BY ". $data['@sort'], $sql);
        }

        if (isset($data['@vars'])) {
            foreach ($data['@vars'] as $k => $v) {
                $sql = str_ireplace("#{".$k."}", $v, $sql);
            }
        }
        
        if (isset($data['@callback'])) {
            $sql = $data['@callback']($item, $data);
        }
        
        if (isset($item['check'])) {
            call_user_func_array($item['check'], [&$sql, &$item, &$data]);
        }
        
        $item['sql'] = $sql;
        return [$item, $data];
    }
    
    private $_values = [];
    private $_bindCount = 0;
    public function parseValue($value) {
        if (is_array($value)) {
            return "(" . implode(",", array_map([$this, 'parseValue'], $value)) . ")";
        }
        
        ++$this->_bindCount;
        $this->_values[$this->_bindCount] = $value;
        return ':AB_'. $this->_bindCount;
    }

}