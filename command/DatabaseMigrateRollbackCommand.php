<?php
namespace Akari\command;

use Akari\Core;
use Akari\system\container\BaseTask;
use Akari\system\db\DBConnection;

class DatabaseMigrateRollbackCommand extends BaseTask {

    public static $command = 'migrate:rollback';
    public static $description = "执行数据库迁移回滚";
    public function handle() {
        if (APP_ENV == ENV_PROD) {
            $envProdTip = $this->ask('你当前运行在生产环境下，确定要执行数据库迁移回滚吗', ['y', 'n']);

            if ($envProdTip == 'n') return false;
        }

        $db = DBConnection::init($params['db'] ?? 'default');
        $baseDir = Core::$baseDir . '/database/migrate/';

        $conn = $db->getConn();

        $maxBatch = $conn->selectOne("SELECT max(`batch`) AS batch FROM migrations");
        if (empty($maxBatch->batch)) {
            return $this->msg("no migrate can rollback");
        }

        $migrates = $conn->select("SELECT * FROM migrations WHERE `batch` = :batch", [
            'batch' => $maxBatch->batch
        ]);

        foreach ($migrates as $migrate) {
            $clsPath = $baseDir . $migrate->migration . '.php';
            if (!file_exists($clsPath)) {
                $this->msg("<error>" . $migrate->migration . " not exists</error>");
                return false;
            }

            $this->msg("<info>rollback: " . $migrate->migration . "</info>");

            $cls = require($clsPath);
            $cls->down( $conn->getSchemaBuilder() );

            $conn->delete("DELETE FROM migrations WHERE id = :id", ['id' => $migrate->id]);
        }
    }

}