<?php

namespace Akari\system\db;

abstract class MigrateTask {

    public $connection = 'default';

    abstract public function up(\Illuminate\Database\Schema\Builder $builder);
    abstract public function down(\Illuminate\Database\Schema\Builder $builder);

}