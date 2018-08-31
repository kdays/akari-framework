<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2018/8/23
 * Time: 下午3:18
 */

namespace Akari\system\conn\table;


use Akari\system\conn\DBConnection;
use Akari\system\conn\DBConnFactory;

class DBMigration {

    protected $connection;
    protected $tables = NULL;

    public static function init(string $config = 'default') {
        return new self(DBConnFactory::get($config));
    }

    public function __construct(DBConnection $DBConnection) {
        $this->connection = $DBConnection;

        $this->updateTables();
    }

    public function getConnection() {
        return $this->connection;
    }

    public function updateTables() {
        $conn = $this->getConnection();
        $cols = $conn->fetch("SELECT `TABLE_NAME` FROM information_schema.TABLES WHERE `TABLE_SCHEMA` = :db_name", [
            'db_name' => $conn->getDatabaseName()
        ]);

        $cols = array_flat($cols, 'TABLE_NAME');
        $this->tables = $cols;
    }

    public function table(string $tableName) {
        return new DBTableMigration($this->getConnection(), $tableName);
    }

    public function exists(string $tableName) {
        if (in_array($tableName, $this->tables)) {
            return TRUE;
        }

        return FALSE;
    }

    public function drop(string $tableName) {
        $sql = sprintf('DROP TABLE `%s`', $tableName);
        $result = $this->getConnection()->query($sql);

        $this->updateTables();
        return $result;
    }

    public function rename(string $fromName, string $toName) {
        $sql = sprintf("RENAME TABLE `%s` TO `%s`", $fromName, $toName);
        $result = $this->getConnection()->query($sql);

        $this->updateTables();
        return $result;
    }

}