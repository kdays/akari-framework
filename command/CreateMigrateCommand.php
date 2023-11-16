<?php

namespace Akari\command;

use Akari\Core;
use Akari\system\container\BaseTask;
use Akari\system\db\DBConnection;

class CreateMigrateCommand extends BaseTask {

    public static $command = 'make:migration';
    public static $description = "创建迁移数据";

    public function handle($params) {
        if (empty($params[0])) {
            $this->msg("<error>请输入创建的迁移名</error>");
            return false;
        }

        $taskDir = Core::$baseDir . '/database/migrate/';
        if (!is_dir($taskDir)) {
            mkdir($taskDir, 0777, true);
        }

        $fileName = date('Y_m_d_His_') . $params[0];
        file_put_contents($taskDir . $fileName . '.php', <<<'EOT'
<?php
use Akari\system\db\MigrateTask;
use Illuminate\Database\Schema\Blueprint;
use \Illuminate\Database\Schema\Builder;

return new class extends MigrateTask
{
    /**
     * Run the migrations.
     */
    public function up(Builder $builder): void
    {
        
    }
    
        /**
     * Reverse the migrations.
     */
    public function down(Builder $builder): void
    {
        
    }
};
EOT
);

        $this->msg("<success>" . $fileName . " migrate created</success>");
    }

}