<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-03
 * Time: 02:20
 */

namespace Akari\system\db;

use Akari\system\util\TextUtil;
use Akari\exception\DBException;

class DBMigration {

    /** @var DBConnection $connection */
    protected $connection;
    protected $tables = [];

    public function __construct(DBConnection $connection) {
        $this->connection = $connection;
        $this->initBasic();
    }

    protected function initBasic() {
        $result = $this->connection->fetch("SELECT `TABLE_NAME` FROM information_schema.TABLES WHERE `TABLE_SCHEMA` = :name", [
            'name' => $this->connection->getDbName()
        ]);

        $this->tables = $result->indexByKey('TABLE_NAME');
    }

    public function exists(string $tableName) {
        return array_key_exists($tableName, $this->tables);
    }

    public function drop(string $tableName) {
        $sql = sprintf('DROP TABLE `%s`', $tableName);
        $result = $this->connection->query($sql);

        $this->initBasic();

        return $result;
    }

    public function rename(string $fromName, string $toName) {
        $sql = sprintf("RENAME TABLE `%s` TO `%s`", $fromName, $toName);
        $result = $this - $this->connection->query($sql);

        $this->initBasic();

        return $result;
    }

    public function table(string $tableName, ?callable $runable) {
        $table = new Table($this->connection, $tableName);

        if (!empty($runable)) {
            if ($runable($table)) {
                return $table->execute();
            }
        }

        return $table;
    }

}

class Table {

    protected $tableName;
    protected $connection;
    protected $isNew = FALSE;
    protected $tableComment;

    protected $indexes = [];
    protected $columns = [];

    public function setComment(string $message) {
        $this->tableComment = $message;
    }

    public function getComment() {
        return $this->tableComment;
    }

    public function __construct(DBConnection $connection, string $tableName) {
        $this->connection = $connection;
        $this->tableName = $tableName;

        // 查询是否有结构 我们先获得数据表结构
        $cols = $connection->fetch("SELECT * FROM information_schema.columns WHERE `table_schema` = :db_name and `table_name` = :table_name", [
            'db_name' => $connection->getDbName(),
            'table_name' => $tableName
        ]);

        foreach ($cols as $col) {
            $columnField = new TableColumn($col);

            $this->columns[ $columnField->name ] = $columnField;
        }


        $this->isNew = $cols->isEmpty();
        if (!$this->isNew) {
            $indexes = $connection->fetch("show keys from " . $tableName);

            $indexColumns = [];
            foreach ($indexes as $indexCol) {
                $index = new TableIndex();
                $index->name = $indexCol['Key_name'];
                if ($indexCol['Non_unique'] != 1) {
                    $index->type = TableIndex::TYPE_UNIQUE;
                }

                $index->comment = $indexCol['Index_comment'];

                $this->indexes[ $index->name ] = $index;
                $indexColumns[ $index->name ][] = $indexCol['Column_name'];
            }

            /**
             * @var string $name
             * @var TableIndex $index
             */
            foreach ($this->indexes as $name => $index) {
                $index->fields = $indexColumns[$name];
            }
        }
    }

    public function exists(string $columnName) {
        return array_key_exists($columnName, $this->columns);
    }

    //// 注册基础方法
    public function text(string $columnName) {
        return $this->_handleFieldType(TableColumn::TYPE_TEXT, $columnName);
    }

    public function string(string $columnName, int $length) {
        $column = $this->_handleFieldType(TableColumn::TYPE_VARCHAR, $columnName);
        $column->setLength($length);

        return $column;
    }

    public function timestamp(string $columnName, $defaultCurrent = TRUE, $autoUpdate = TRUE) {
        $column = $this->_handleFieldType(TableColumn::TYPE_TIMESTAMP, $columnName);
        if (!$autoUpdate && $defaultCurrent) {
            $column->setDefault('CURRENT_TIMESTAMP');
        } elseif ($autoUpdate && $defaultCurrent) {
            $column->setDefault('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        }

        return $column;
    }

    public function tinyInteger(string $columnName) {
        return $this->_handleFieldType(TableColumn::TYPE_TINYINT, $columnName);
    }

    public function enum(string $columnName, array $allowed) {
        $column = $this->_handleFieldType('enum', $columnName);
        $column->setENUM($allowed);

        return $column;
    }

    public function decimal(string $columnName, int $digits, int $point) {
        $column = $this->_handleFieldType('decimal', $columnName);
        $column->modifyField('length', $digits . ',' . $point);

        return $column;
    }

    public function integer(string $columnName) {
        return $this->_handleFieldType(TableColumn::TYPE_INT, $columnName);
    }

    public function json(string $columnName) {
        return $this->_handleFieldType('json', $columnName);
    }

    public function increments(string $columnName) {
        $col = $this->_handleFieldType(TableColumn::TYPE_INT, $columnName);
        $col->modifyField('primary', 1);
        $col->autoIncrement();

        return $col;
    }

    /**
     * @param string $columnName
     * @return TableColumn|null
     */
    public function column(string $columnName) {
        return $this->columns[$columnName] ?? NULL;
    }

    public function index(string $indexName) {
        if (!isset($this->indexes[$indexName])) {
            $index = new TableIndex();
            $index->name = $indexName;
            $index->modifyFields[] = '*';

            $this->indexes[ $indexName ] = $index;

        }

        return $this->indexes[ $indexName ];
    }

    /**
     * @param string $type
     * @param string $colName
     * @return TableColumn
     */
    protected function _handleFieldType(string $type, string $colName) {
        if (!array_key_exists($colName, $this->columns)) {
            $column = new TableColumn(NULL);
            $column->name = $colName;
            $column->type = $type;

            $this->columns[$colName] = $column;
        } else {
            $column = $this->columns[$colName];
            if ($column->type != $type) {
                $column->type = $type;
                $column->modifyFields[] = "type";
            }
        }

        return $this->columns[$colName];
    }


    protected function getColumnModifySQL(TableColumn $stub) {
        $sql = $stub->type;

        // 处理ENUM
        if ($stub->type == TableColumn::TYPE_ENUM) {
            $sql .= "('" . implode("','", $stub->enumFields) . "')";
        } elseif ($stub->length > 0) {
            $sql .= "(" . $stub->length . ")";
        }

        if ($stub->unsigned) {
            $sql .= " UNSIGNED ";
        }

        if (!$stub->nullable) {
            $sql .= " NOT NULL ";
        } else {
            $sql .= ' NULL ';
        }

        if ($stub->defaultValue !== NULL && $stub->type != TableColumn::TYPE_TEXT) {
            $value = $stub->defaultValue;
            $specialDefaultValues = [
                'CURRENT_TIMESTAMP',
                'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
            ];

            if (is_string($value) && !in_array($value, $specialDefaultValues)) { //字符串类型时 我们包裹一下
                $value = "'$value'";
            }
            $sql .= sprintf(' DEFAULT %s', $value);
        }

        if (!empty($stub->remark)) {
            $sql .= sprintf(" COMMENT '%s'", $stub->remark);
        }

        if (!empty($stub->extra)) {
            $sql .= " " . $stub->extra;
        }

        return $sql;
    }

    protected function getStubSQL() {
        $sqls = [];
        $pk = [];

        if ($this->isNew) {
            $cols = [];

            foreach ($this->columns as $key => $stub) {
                $mSql = $this->getColumnModifySQL($stub);

                $cols[] = sprintf('`%s` %s', $key, $mSql);
                if ($stub->primary) {
                    $pk[] = $stub->name;
                }
            }

            if (!empty($pk)) {
                $cols[] = 'PRIMARY KEY (' . implode(",", $pk) . ")";
            }

            $sqls[] = sprintf('CREATE TABLE %s (%s) COMMENT = "%s"', $this->tableName, implode(",\n", $cols), $this->tableComment);
        } else {
            $isModifyPK = FALSE;

            /** @var TableColumn $stub */
            foreach ($this->columns as $stub) {
                if (empty($stub->modifyFields)) {
                    continue;
                }

                $sql = sprintf('ALTER TABLE `%s`', $this->tableName);
                if (in_array('*', $stub->modifyFields)) {
                    $sql .= sprintf(" ADD COLUMN `%s`", $stub->name);
                } elseif (in_array('_DROP', $stub->modifyFields)) {
                    $sqls[] .= $sql . sprintf(" DROP COLUMN `%s`", $stub->name);
                    continue; //drop column, not need next step.
                } else {
                    $sql .= sprintf(" CHANGE COLUMN `%s` `%s`", $stub->oldName, $stub->name);
                }

                // 处理Parimary Key
                if ($stub->primary) {
                    $pk[] = $stub->name;
                }

                if (in_array('primary', $stub->modifyFields)) {
                    $isModifyPK = TRUE;
                }

                $sql .= $this->getColumnModifySQL($stub);
                $sqls[] = $sql;
            }

            if ($isModifyPK) {
                $sqls[] = sprintf('ALTER TABLE `%s` DROP PRIMARY KEY %s',
                    $this->tableName,
                    empty($pk) ? '' : ', ADD PRIMARY KEY (' . implode(",", $pk) . ")"
                );
            }
        }

        return $sqls;
    }

    protected function getIndexSQL() {
        $sqls = [];

        $typeMap = [ //ADD INDEX `aaa` USING BTREE (`fid`, `type`) comment '';
            TableIndex::TYPE_NORMAL => 'INDEX',
            TableIndex::TYPE_UNIQUE => 'UNIQUE',
            TableIndex::TYPE_FULLTEXT => 'FULLTEXT'
        ];

        /**
         * @var string $indexName
         * @var TableIndex $index
         */
        foreach ($this->indexes as $indexName => $index) {
            if (empty($index->fields) || empty($index->modifyFields)) {
                continue;
            }

            $sql = sprintf('ALTER TABLE `%s` ', $this->tableName);
            if (!in_array('*', $index->modifyFields)) {
                $sql .= sprintf(' DROP INDEX `%s`', $index->name);
                if (in_array('_DROP', $index->modifyFields)) {
                    $sqls[] = $sql;
                    continue;
                }

                $sql .= ",";
            }

            $sql .= sprintf('ADD %s `%s` (%s) comment "%s"',
                $typeMap[$index->type],
                $index->name,
                implode(",", $index->fields),
                $index->comment
            );

            $sqls[] = $sql;
        }

        return $sqls;
    }

    public function execute() {
        $sqls = $this->sqls();

        if (empty($sqls)) {
            return TRUE;
        }

        $this->connection->beginTransaction();
        foreach ($sqls as $sql) {
            try {
                $this->connection->query($sql);
            } catch (DBException $e) {
                $this->connection->rollback();
                throw $e;
            }
        }

        if ($this->connection->inTransaction()) {
            $this->connection->commit();
        }

        return count($sqls);
    }

    public function sqls() {
        $extraSqls = [];

        $sqls = array_merge($extraSqls, $this->getStubSQL(), $this->getIndexSQL());

        return $sqls;
    }

}

class TableColumn {

    const TYPE_VARCHAR = 'varchar';
    const TYPE_TEXT = 'text';
    const TYPE_INT = 'int';
    const TYPE_TINYINT = 'tinyint';
    const TYPE_ENUM = 'enum';
    const TYPE_TIMESTAMP = 'timestamp';
    const TYPE_DECIMAL = 'decimal'; // for bank

    public static $stringTypes = [self::TYPE_VARCHAR, self::TYPE_TEXT];

    public $name;

    public $oldName;

    public $length;

    public $type = self::TYPE_TEXT;

    public $enumFields = [];

    public $defaultValue = NULL;

    public $remark = '';

    public $primary = FALSE;

    public $nullable = FALSE;

    public $unsigned = FALSE;

    public $extraFields = "";

    public $extra;

    public $modifyFields = [];

    public function __construct(?array $column) {
        if (!empty($column)) {
            $stub = $this;
            $stub->name = $column['COLUMN_NAME'];
            $stub->type = $column['DATA_TYPE'];
            if (!empty($column['CHARACTER_MAXIMUM_LENGTH'])) {
                $stub->length = (int) $column['CHARACTER_MAXIMUM_LENGTH'];
            } else {
                if ($stub->type == self::TYPE_ENUM) {
                    $matches = substr($column['COLUMN_TYPE'], strpos($column['COLUMN_TYPE'], '(') + 1, -1);
                    $matches = json_decode('[' . $matches . ']', TRUE);

                    $stub->enumFields = $matches;
                } else {
                    preg_match('/(\d+)/', $column['COLUMN_TYPE'], $matches);
                    $stub->length = (int) ($matches[0] ?? 0);
                }
            }
            $stub->remark = $column['COLUMN_COMMENT'];
            $stub->primary = $column['COLUMN_KEY'] == 'PRI';
            $stub->extraFields = $column['EXTRA'];
            $stub->oldName = $stub->name;
            $stub->defaultValue = $column['COLUMN_DEFAULT'];

            $stub->nullable = $column['IS_NULLABLE'] != 'NO';
            if (TextUtil::exists($column['COLUMN_TYPE'], 'unsigned')) {
                $stub->unsigned = TRUE;
            }
        } else {
            $this->modifyFields[] = "*";
        }
    }

    public function modifyField(string $fieldName, $value) {
        if ($this->$fieldName != $value) {
            $this->modifyFields[] = $fieldName;
        }

        $this->$fieldName = $value;

        return $this;
    }


    public function setLength(int $length) {
        return $this->modifyField('length', $length);
    }

    public function setType(string $type) {
        return $this->modifyField('type', $type);
    }

    public function setDefault(string $defaultValue) {
        return $this->modifyField('defaultValue', $defaultValue);
    }

    public function setRemark(string $remark) {
        return $this->modifyField('remark', $remark);
    }

    public function nullable($to = TRUE) {
        return $this->modifyField('nullable', !!$to);
    }

    public function setENUM(array $allowed) {
        return $this->modifyField('enumFields', $allowed);
    }

    public function unsigned($to = TRUE) {
        if (in_array($this->type, self::$stringTypes)) {
            throw new DBException("Field " . $this->name . " is TEXT type. can't SET unsigned");
        }

        return $this->modifyField('unsigned', !!$to);
    }

    public function autoIncrement() {
        return $this->modifyField('extra', 'AUTO_INCREMENT');
    }

    public function renameTo(string $toName) {
        if (empty($this->oldName)) {
            throw new DBException("modifyName can not run on create mode");
        }

        if ($this->oldName == $toName) {
            throw new DBException("modifyName not change.");
        }

        $this->name = $toName;
        $this->modifyFields[] = 'name';

        return $this;
    }

    public function afterColumn(string $columnName) {
        $this->extraFields .= " AFTER `" . $columnName . "`";
    }

    public function drop() {
        $this->modifyFields[] = '_DROP';
    }
}

class TableIndex {

    const TYPE_UNIQUE = 'UNIQUE';
    const TYPE_NORMAL = 'NORMAL';
    const TYPE_FULLTEXT = 'FULLTEXT';

    public $name;

    public $fields = [];

    public $type = self::TYPE_NORMAL;

    public $comment = '';

    public $modifyFields = [];

    public function setFields(...$fields) {
        $this->fields = $fields;
        $this->modifyFields[] = 'fields';

        return $this;
    }

    protected function _handleModify(string $fieldName, $value) {
        if ($this->$fieldName != $value) {
            $this->modifyFields[] = $fieldName;
        }

        $this->$fieldName = $value;

        return $this;
    }

    public function setType(string $type) {
        return $this->_handleModify('type', $type);
    }

    public function setRemark(string $remark) {
        return $this->_handleModify('remark', $remark);
    }

    public function drop() {
        $this->modifyFields[] = '_DROP';
    }


}
