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
    public function execute($id, array $data) {
        $lists = $this->map->lists;

        $mapId = get_class($this->map) . "@" . $id;
        if (!isset($lists[$id])) {
            throw new DBException("Not Found SQLMap:" . $mapId);
        }

        $item = $lists[$id];
        $type = strtoupper(explode(".", $id)[0]);

        $connection = $this->connection;
        $connection->appendMsg(" [FROM: " . $mapId . "]");

        list($item, $data) = $this->prepareDataForSql($item, $data, $mapId);
        $sql = $item['sql'];
        $vars = [];

        // 增加全局参数的替换
        $commArgs = array_merge(
            ['TABLE_NAME' => $this->map->table],
            $this->map->args,
            empty($data['args']) ? [] : $data['args']);

        foreach ($commArgs as $k => $v) {
            $sql = str_replace('@' . $k, $v, $sql);
        }

        foreach ($data as $k => $v) {
            if ($k[0] == '@')   continue;

            if (is_array($v) && isset($data['@in']) && in_array($k, $data['@in'])) {
                $sql = str_ireplace(":" . $k, $this->parseValue($v), $sql);
            } else {
                $vars[$k] = $v;   
            }
        }

        foreach ($this->_values as $k => $v) {
            $vars['AB_' . $k] = $v;
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
            if (empty($result)) { // 如果没有InsertId
                $result = TRUE;
            }
        } else {
            $result = $connection->query($sql, $vars);
        }

        $this->close();
        $connection->resetAppendMsg();

        return $result;
    }

    public function close() {
        $this->_values = [];
        $this->_bindCount = 0;
    }

    private function prepareDataForSql($item, $data, $mapId) {
        $sql = $item['sql'];

        if (isset($item['required'])) {
            foreach ($item['required'] as $key) {
                if (!isset($data[$key]) 
                    and !isset($data['@vars'][$key]) 
                    and !isset($data['@bind'][$key])) {
                    throw new MissingDbValue($mapId, $key);
                }
            }
        }

        if (isset($item['var'])) {
            foreach ($item['var'] as $key => $value) {
                if (!array_key_exists($key, $data)) $data[$key] = $value;
            }
        }

        if (isset($data['@limit'])) {
            $sql = str_ireplace("#limit", DBUtil::makeLimit($data['@limit']), $sql);
        } 

        if (isset($data['@keys'])) {
            $sql = str_ireplace(
                "#keys",
                DBUtil::mergeMetaKeys($data['@keys'], $this->connection),
                $sql
            );
        }

        if (isset($data['@sort'])) {
            $sql = str_ireplace("#sort", " ORDER BY " . $data['@sort'], $sql);
        }

        if (isset($data['@vars'])) {
            foreach ($data['@vars'] as $k => $v) {
                $sql = str_ireplace("#{" . $k . "}", $v, $sql);
            }
        }

        if (isset($data['@bind'])) {
            foreach ($data['@bind'] as $k => $v) {
                $sql = str_ireplace(":" . $k, $this->parseValue($v), $sql);
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
            return implode(",", array_map([$this, 'parseValue'], $value));
        }

        ++$this->_bindCount;
        $this->_values[$this->_bindCount] = $value;

        return ':AB_' . $this->_bindCount;
    }
}
