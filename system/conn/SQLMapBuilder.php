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
        
        $mapId = get_class($this->map). "@" . $id;
        if (!isset($lists[$id])) {
            throw new DBException("Not Found SQLMap:" . $mapId);
        }
        
        $item = $lists[$id];
        $type = strtoupper(explode(".", $id)[0]);
        
        $connection = $this->connection;
        $connection->appendMsg(" [FROM: ". $mapId. "]");
        
        list($item, $data) = $this->prepareDataForSql($item, $data);
        $sql = $item['sql'];
        $vars = [];
        
        foreach ($data as $k => $v) {
            if ($k[0] == '@')   continue;
            $vars[$k] = $v;
        }
        
        foreach ($this->_values as $k => $v) {
            $vars['AB_'. $k] = $v;
        }
        
        $result = NULL;
        if ($type == self::TYPE_COUNT) {
            $result = $connection->fetchValue($sql, $vars);
        } elseif ($type == self::TYPE_SELECT) {
            $result = $connection->fetch($sql, $vars, \PDO::FETCH_ASSOC);
        } elseif ($type == self::TYPE_ROW) {
            $result = $connection->fetchOne($sql, $vars, \PDO::FETCH_ASSOC);
        } elseif ($type == self::TYPE_INSERT) {
            $result = $connection->query($sql, $vars, TRUE);
        } else {
            $result = $connection->query($sql, $vars);
        }
        
        $connection->resetAppendMsg();
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
        
        if (isset($item['var'])) {
            foreach ($item['var'] as $key => $value) {
                if (!array_key_exists($key, $data)) $data[$key] = $value;
            }
        }
        
        if (isset($data['@limit'])) {
            $ll = $data['@limit'];
            $sql = str_ireplace("#limit", (is_array($ll) ? ' LIMIT '. $ll[0]. ",". $ll[1] : ' LIMIT '.$ll), $sql);
        } 
        
        if (isset($data['@keys'])) {
            $rr = [];
            foreach ($data['@keys'] as $key) {
                $rr[] = $this->connection->getMetaKey($key) . "  = :$key";
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