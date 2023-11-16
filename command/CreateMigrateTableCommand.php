<?php

namespace Akari\command;

use Akari\system\container\BaseTask;
use Akari\system\db\DBConnection;
use Illuminate\Database\Schema\Blueprint;

class CreateMigrateTableCommand extends BaseTask {

    public static $command = 'migrate:install';
    public static $description = "安装数据库迁移表";
    public function handle($params) {
        $db = DBConnection::init($params['db'] ?? 'default');

        if (!$db->migration()->hasTable('migrations')) {
            $db->migration()->create("migrations", function(Blueprint $table) {
                $table->id();
                $table->string('migration');
                $table->integer('batch');
            });

            $this->msg("<success>migrations created.</success>");
        } else {
            $this->msg("<info>migrations table exists.</info>");
        }
    }

}