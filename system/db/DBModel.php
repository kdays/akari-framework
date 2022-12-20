<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-03
 * Time: 02:33
 */

namespace Akari\system\db;

use Akari\system\util\TextUtil;
use Akari\exception\DBException;
use Akari\system\util\ArrayUtil;
use Akari\system\util\Collection;

abstract class DBModel {

    protected static $_cachedBuilder = [];

    protected $fromQuery = FALSE;

    public static function findById($id) {
        $pk = static::getPrimaryKey();

        return static::findFirst([$pk => $id]);
    }

    public static function query($join, $columns = NULL, $where = NULL) {
        if (is_array($join) && is_null($columns)) {// 如果有Fields字段 我们单独处理
            $columns = $join;
            $join = $join['FIELDS'] ?? $join['fields'] ?? '*';

            unset($columns['FIELDS']);
        }

        $builder = static::getSQLBuilder();
        $data = $builder->select(static::getTableName(), $join, $columns, $where);

        $builder->reset();

        return Collection::make($data);
    }

    /**
     * @param $join
     * @param null $columns
     * @param null $where
     * @return Collection
     */
    public static function find($join, $columns = NULL, $where = NULL) {
        if (is_array($join) && is_null($columns)) {// 如果有Fields字段 我们单独处理
            $columns = $join;
            $join = $join['FIELDS'] ?? $join['fields'] ?? '*';

            unset($columns['FIELDS']);
        }

        $builder = static::getSQLBuilder();
        $data = $builder->select(static::getTableName(), $join, $columns, $where);

        foreach ($data as $key => $value) {
            $model = static::toModel($value);
            $model->fromQuery = TRUE;

            $data[$key] = $model;
        }

        $builder->reset();

        return Collection::make($data);
    }

    public static function findFirst($join, $columns = NULL, $where = NULL) {
        if (is_array($join) && is_null($columns)) {// 如果有Fields字段 我们单独处理
            $columns = $join;
            $join = $join['FIELDS'] ?? $join['fields'] ?? '*';

            unset($columns['FIELDS']);
        }

        $builder = static::getSQLBuilder();
        $data = $builder->get(static::getTableName(), $join, $columns, $where);
        $builder->reset();

        if (empty($data)) {
            return NULL;
        }

        $result = static::toModel($data);
        $result->fromQuery = TRUE;

        return $result;
    }

    public static function insert(array $data) {
        $builder = static::getSQLBuilder();

        $result = $builder->insert( static::getTableName(), $data );
        if ($result) {
            $id = $builder->id();
            $result->closeCursor();

            return $id;
        }

        $result->closeCursor();
        $builder->reset();
        return TRUE;
    }

    public static function update($where, $data) {
        $builder = static::getSQLBuilder();

        $result = $builder->update(static::getTableName(), $data, $where);
        $builder->reset();

        return $result;
    }

    public static function delete($where) {
        $builder = static::getSQLBuilder();

        $result = $builder->delete(static::getTableName(), $where);
        $builder->reset();

        return $result;
    }

    public static function count($join = NULL, $column = NULL, $where = NULL) {
        $builder = static::getSQLBuilder();
        if (isset($join['conditions'])) {
            $join = $join['conditions'];
        }

        $result = $builder->count(static::getTableName(), $join, $column, $where);
        $builder->reset();
        return $result;
    }

    /**
     * @return SQLBuilder
     */
    protected static function getSQLBuilder() {
        if (!isset(self::$_cachedBuilder[static::class])) {
            self::$_cachedBuilder[static::class] = new SQLBuilder( static::getConnection() );
        }

        return self::$_cachedBuilder[static::class];
    }

    public static function setDebugEnabled($isEnabled) {
        static::getSQLBuilder()->debug = $isEnabled;
    }

    public static function action(callable $action) {
        static::getSQLBuilder()->action($action);
    }

    public function remove() {
        $pk = static::getPrimaryKey();
        $mKey = $this->columnMap()[$pk] ?? $pk;

        if (empty($this->$mKey)) {
            throw new DBException("No Primary Id");
        }

        return static::delete([$pk => $this->$mKey]);
    }

    public function save() { // 保存字段
        $pk = static::getPrimaryKey();
        $mKey = $this->columnMap()[$pk] ?? $pk;

        if ($this->fromQuery) {
           return static::update([$pk => $this->$mKey], $this->toArray());
        } else {
            if (empty($this->$mKey)) {
                $id = static::insert($this->toArray());
                $this->$mKey = $id;

                return $id;
            }

            $ifExists = static::findById($this->$mKey);
            if ($ifExists) {
                $ifExists = null;
                return static::update([$pk => $this->$mKey], $this->toArray());
            }

            return static::insert($this->toArray());
        }
    }

    protected static function getPrimaryKey() { // 获得主键字段
        return 'id';
    }

    public function getId() {
        $pk = static::getPrimaryKey();
        $mKey = $this->columnMap()[$pk] ?? $pk;

        if (empty($this->$mKey)) {
            return static::getSQLBuilder()->id(); // maybe insert?
        }

        return $this->$mKey;
    }

    public static function getTableName() {
        $cls = ArrayUtil::last(explode(NAMESPACE_SEPARATOR, static::class));
        $cls[0] = strtolower($cls[0]);

        return TextUtil::snakeCase($cls);
    }

    /**
     * @return DBConnection
     */
    public static function getConnection() {
        return DBConnection::init();
    }

    public static function toModel(array $in) {
        $model = new static();
        foreach ($model->columnMap() as $dbKey => $modelKey) {
            $model->$modelKey = $in[$dbKey] ?? NULL;
        }

        return $model;
    }

    public function toArray() {
        $result = [];
        foreach ($this->columnMap() as $dbKey => $modelKey) {
            $result[$dbKey] = $this->$modelKey;
        }

        return $result;
    }

    /**
     * {databaseKey} -> {modelKey}
     */
    abstract public function columnMap() :array;

}
