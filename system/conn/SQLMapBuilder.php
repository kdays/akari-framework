<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/8/30
 * Time: 下午6:26
 */

namespace Akari\system\conn;

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
    public function execute(string $id, array $data) {
        $items = $this->map->items;

        $mapId = get_class($this->map) . "@" . $id;
        if (!isset($items[$id])) {
            throw new DBException("not found map item: ". $mapId);
        }

        $item = $items[$id];
        $type = strtoupper(explode(".", $id)[0]);

        $sql = $item['sql'];
        $connection = $this->connection;

        if (isset($item['vars'])) {
            foreach ($item['vars'] as $key => $value) {
                if (!array_key_exists($key, $data)) {
                    $data[$key] = $value;
                }
            }
        }

        if (isset($data['@keys'])) {
            $sql = str_ireplace("#keys", DBUtil::mergeMetaKeys($data['@keys'], $connection), $sql);
        }

        if (isset($data['@limit'])) {
            $sql = str_ireplace("#limit", DBUtil::makeLimit($data['@limit']), $sql);
        }

        if (isset($data['@sort'])) {
            $sql = str_ireplace("#sort", " ORDER BY " . $data['@sort'], $sql);
        }

        if (isset($data['@vars'])) {
            foreach ($data['@vars'] as $k => $v) {
                $sql = str_ireplace("#{" . $k . "}", $v, $sql);
            }
        }

        $connection->appendMsg(" [FROM: " . $mapId . "]");

        // 增加全局参数的替换
        $commArgs = array_merge($this->map->args, $data['args'] ?? []);
        foreach ($commArgs as $k => $v) {
            $sql = str_replace('@' . $k, $v, $sql);
        }

        $bindData = [];
        foreach ($data as $k => $v) {
            if ($k[0] == '@') {
                continue;
            }

            if (is_array($v)) {
                $sql = str_ireplace(":" . $k, $this->parseValue($v), $sql);
            } else {
                $bindData[$k] = $v;
            }
        }

        foreach ($this->_values as $k => $v) {
            $bindData['AB_' . $k] = $v;
        }

        $result = NULL;
        if ($type == self::TYPE_COUNT) {
            $result = $connection->fetchValue($sql, $bindData);
        } elseif ($type == self::TYPE_SELECT) {
            $result = $connection->fetch($sql, $bindData, \PDO::FETCH_ASSOC);
        } elseif ($type == self::TYPE_ROW) {
            $result = $connection->fetchOne($sql, $bindData, \PDO::FETCH_ASSOC);
        } elseif ($type == self::TYPE_INSERT) {
            $result = $connection->query($sql, $bindData, TRUE);
            if (empty($result)) { // 如果没有InsertId
                $result = TRUE;
            }
        } else {
            $result = $connection->query($sql, $bindData);
        }

        $this->close();
        $connection->resetAppendMsg();

        return $result;
    }

    public function close() {
        $this->_values = [];
        $this->_bindCount = 0;
    }

    private $_values = [];
    private $_bindCount = 0;
    public function parseValue($value) {
        if (is_array($value)) {
            return implode(",", array_map([$this, 'parseValue'], $value));
        }

        ++$this->_bindCount;
        $this->_values[$this->_bindCount] = $value;

        return ':AB_' . $this->_bindCount;
    }
}
