<?php


namespace Akari\system\db\mongo;

use Akari\system\util\Collection;

class MongoMigration {

    protected $conn;

    public function __construct(MongoConnection $connection) {
        $this->conn = $connection;
    }

    public function listCollections() {
        $result = $this->conn->getQuery()->listCollections();
        $data = [];
        foreach ($result as $item) {
            $data[] = $item->getName();
        }

        return $data;
    }

    public function createCollection( string $name) {
        return $this->conn->getQuery()->createCollection($name);
    }

    public function dropCollection(string $name) {
        return $this->conn->getQuery()->dropCollection($name);
    }

    public function createIndex(string $collection,  array $keys) {
        return $this->conn->getQuery()->selectCollection($collection)->createIndex($keys);
    }

    public function listIndexes(string $collection) {
        return $this->conn->getQuery()->selectCollection($collection)->listIndexes();
    }

    public function dropIndex(string $collection, $index) {
        return $this->conn->getQuery()->selectCollection($collection)->dropIndex($index);
    }


}
