<?php

namespace Akari\system\db\mongo;

use MongoDB\BSON\ObjectId;
use Akari\system\util\TextUtil;
use Akari\system\util\ArrayUtil;
use Akari\system\util\Collection;

abstract class MongoCollection {

    public $_id;

    protected $connection;
    protected $fromQuery = FALSE;

    protected static $_cachedBuilder = [];

    public static function getConnection(): MongoConnection {
        return MongoConnection::init();
    }

    public static function getCollectionName() {
        $cls = ArrayUtil::last(explode(NAMESPACE_SEPARATOR, static::class));
        $cls[0] = strtolower($cls[0]);

        return TextUtil::snakeCase($cls);
    }

    public static function toModel($in) {
        $model = new static();
        foreach ($model->columnMap() + ['_id' => '_id'] as $dbKey => $modelKey) {
            $model->$modelKey = $in->$dbKey ?? NULL;
        }

        return $model;
    }

    public static function usingObjectId() {
        return TRUE;
    }

    /**
     * @return MongoBuilder
     */
    public static function getMongoBuilder() {
        if (!isset(self::$_cachedBuilder[static::class])) {
            self::$_cachedBuilder[static::class] = new MongoBuilder(static::getConnection(), static::getCollectionName(), [
                'usingObjectId' => static::usingObjectId()
            ]);
        }

        return self::$_cachedBuilder[static::class];
    }

    public function toArray() {
        $result = [];
        foreach ($this->columnMap() + ['_id' => '_id'] as $dbKey => $modelKey) {
            if ($dbKey === '_id' && empty($this->_id)) {
                continue;
            }
            $result[$dbKey] = $this->$modelKey;
        }

        return $result;
    }

    /**
     * {databaseKey} -> {modelKey}
     */
    abstract public function columnMap() :array;

    public function getId() {
        return $this->_id instanceof ObjectId ? (string) $this->_id : $this->_id;
    }

    public static function find(array $conds) {
        $builder = static::getMongoBuilder();
        $q = $builder->find($conds);
        $result = [];

        foreach ($q as $item) {
            $model = static::toModel($item);
            $model->fromQuery = TRUE;
            $result[] = $model;
        }

        return Collection::make($result);
    }

    public static function findFirst(array $conds) {
        $builder = static::getMongoBuilder();
        $data = $builder->findFirst($conds);

        if ($data) {
            $model = self::toModel($data);
            $model->fromQuery = TRUE;

            return $model;
        }

        return NULL;
    }

    public static function findById($id) {
        return self::findFirst(['_id' => $id]);
    }

    public static function update(array $filter, array $data) {
        $builder = static::getMongoBuilder();

        return $builder->update($filter, $data);
    }

    public static function insert(array $data) {
        $builder = static::getMongoBuilder();

        return $builder->insert($data);
    }

    public static function count(array $filter) {
        $builder = static::getMongoBuilder();

        return $builder->count($filter);
    }

    public static function delete(array $filter) {
        if (empty($filter)) {
            return FALSE;
        }

        $builder = static::getMongoBuilder();

        return $builder->delete($filter);
    }

    public function save() {
        if ($this->fromQuery) {
            return static::update(['_id' => $this->_id], $this->toArray())->getMatchedCount() > 0;
        }

        if (empty($this->_id)) {
            // usingObjectId关闭时 自动生成
            if (!static::usingObjectId()) {
                $this->_id = static::seqId();
            }

            $result = static::insert($this->toArray());
            $this->_id = $result->getInsertedId();

            return $this->getId();
        }

        $ifExists = static::findById($this->_id);
        if ($ifExists) {
            return static::update(['_id' => $this->_id], $this->toArray())->getMatchedCount() > 0;
        }

        if (static::insert($this->toArray())->getInsertedCount() > 0) {
            return $this->getId();
        }

        return FALSE;
    }

    public function remove() {
        if (!$this->fromQuery) {
            return FALSE;
        }

        return self::delete(['_id' => $this->_id])->getDeletedCount() > 0;
    }

    public static function seqId(string $key = NULL, string $seqColName = 'seq') {
        if (static::usingObjectId()) {
            throw new \RuntimeException('Mongo.Seq Error: now using ObjectId');
        }

        if ($key === NULL) {
            $key = static::getCollectionName();
        }

        $result = static::getConnection()
            ->getQuery()
            ->selectCollection($seqColName)
            ->findOneAndUpdate(['_id' => $key], ['$inc' => ['v' => 1]], ['upsert' => TRUE]);

        if (empty($result)) {
            return self::seqId($key, $seqColName);  // no field, repeat
        }

        return $result['v'];
    }

}
