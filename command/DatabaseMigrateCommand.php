<?php

namespace Akari\command;

use Akari\Core;
use Akari\system\container\BaseTask;
use Akari\system\db\DBConnection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use ReflectionClass;

class DatabaseMigrateCommand extends BaseTask {

    public static $command = 'migrate';
    public static $description = "执行数据库迁移";

    protected $connections = [];
    protected $migrations = [];

    protected $batchIDMap = [];

    protected function initConnection($name) {
        if (isset($this->connections[$name])) return false;

        $connection = DBConnection::init($name)->getConn();

        $this->connections[$name] = $connection;
        $this->migrations[$name] = $connection->select("SELECT * FROM migrations");

        return $this->connections[$name];
    }

    protected function _existsMigrate($name, $migrateName) {
        foreach ($this->migrations[$name] as $item) {
            if ($migrateName == $item->migration) return true;
        }

        return false;
    }

    protected function getBatchID($name) {
        if (isset($this->batchIDMap[$name])) return $this->batchIDMap[$name];

        $id = 0;
        foreach ($this->migrations[$name] as $migration) {
            $id = max($migration->batch, $id);
        }

        $this->batchIDMap[$name] = $id + 1;
        return $this->batchIDMap[$name];
    }

    public function handle() {
        if (APP_ENV == ENV_PROD) {
            $envProdTip = $this->ask('你当前运行在生产环境下，确定要执行数据库迁移吗', ['y', 'n']);

            if ($envProdTip == 'n') return false;
        }

        $dirScanner = new \DirectoryIterator(Core::$baseDir . '/database/migrate/');
        $count = 0;
        foreach ($dirScanner as $path) {
            if ($path->isDot()) continue;
            $base = $path->getBaseName('.php');

            $cls = require($path->getPathName());
            $cls = new $cls();

            $conn = $this->initConnection($cls->connection);
            if ($this->_existsMigrate($cls->connection, $base)) continue;

            $this->msg("Migrate: " . $base);
            ++$count;

            $batchID = $this->getBatchID($cls->connection);

            try {
                $cls->up($conn->getSchemaBuilder());
                $conn->insert("INSERT INTO `migrations` (`migration`, `batch`) VALUES (:name, :batch)", [
                    'name' => $base,
                    'batch' => $batchID
                ]);

                $this->msg("<success>$base done.</success>");

            } catch (\Exception $e) {
                $this->msg("<error>$base: {$e->getMessage()}</error>");
                //$this->rollBacks();

                return false;
            }
        }

        if ($count == 0) {
            $this->msg("no migrates can execute.");
        }
    }

    protected function rollBacks() {
        foreach ($this->connections as $connection) {
            $connection->rollback();
        }
    }

}