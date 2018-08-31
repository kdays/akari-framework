<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/11/22
 * Time: 下午3:46
 */

namespace Akari\system\conn\table;

use Akari\model\DatabaseModel;
use Akari\system\conn\DBException;
use Akari\system\conn\DBConnection;
use Akari\system\conn\DBConnFactory;

class DBTableMigration {

    protected $tableName;

    protected $stub = [];
    protected $indexes = [];

    protected $connection;
    protected $isCreateMode;
    protected $doDropTable = FALSE;

    public static function init($tableName, $config = 'default') {
        return new self(DBConnFactory::get($config), $tableName);
    }

    public static function loadModel($className, $config = 'default') {
        /** @var DatabaseModel $class */
        $class = new $className();

        $m = self::init($class->tableName(), $config);
        $m->loadModelClass($className);

        return $m;
    }

    public function getConnection() {
        return $this->connection;
    }

    public function __construct(DBConnection $DBConnection, $tableName) {
        $this->tableName = $tableName;
        $this->connection = $DBConnection;

        // 查询是否有结构 我们先获得数据表结构
        $cols = $DBConnection->fetch("SELECT * FROM information_schema.columns WHERE `table_schema` = :db_name and `table_name` = :table_name", [
            'db_name' => $DBConnection->getDatabaseName(),
            'table_name' => $tableName
        ]);

        foreach ($cols as $col) {
            $stub = new TableColumnStub();
            $stub->name = $col['COLUMN_NAME'];
            $stub->type = $col['DATA_TYPE'];
            if (!empty($col['CHARACTER_MAXIMUM_LENGTH'])) {
                $stub->length = (int) $col['CHARACTER_MAXIMUM_LENGTH'];
            } else {
                preg_match('/(\d+)/', $col['COLUMN_TYPE'], $matches);
                $stub->length = (int) $matches[0];
            }
            $stub->remark = $col['COLUMN_COMMENT'];
            $stub->isPrimary = $col['COLUMN_KEY'] == 'PRI';
            $stub->extra = $col['EXTRA'];
            $stub->oldName = $stub->name;
            $stub->defaultValue = $col['COLUMN_DEFAULT'];

            $stub->allowNull = $col['IS_NULLABLE'] != 'NO';
            if (in_string($col['COLUMN_TYPE'], 'unsigned')) {
                $stub->unsigned = TRUE;
            }

            if (in_string($col['COLUMN_TYPE'], 'zerofill')) {
                $stub->zerofill = TRUE;
            }

            $this->stub[ $stub->name ] = $stub;
        }

        $this->isCreateMode = empty($this->stub);
        if (!$this->isCreateMode) {
            $indexes = $DBConnection->fetch("show keys from " . $tableName);

            $indexColumns = [];
            foreach ($indexes as $indexCol) {
                $index = new TableIndexStub();
                $index->name = $indexCol['Key_name'];
                if ($indexCol['Non_unique'] != 1) {
                    $index->type = TableIndexStub::TYPE_UNIQUE;
                }

                $index->comment = $indexCol['Index_comment'];

                $this->indexes[ $index->name ] = $index;
                $indexColumns[ $index->name ][] = $indexCol['Column_name'];
            }

            /**
             * @var string $name
             * @var TableIndexStub $index
             */
            foreach ($this->indexes as $name => $index) {
                $index->fields = $indexColumns[$name];
            }
        }
    }

    public function hasColumn($name) {
        // 处理
        foreach ($this->stub as $columnName => $column) {
            if (strtolower($name) == strtolower($columnName)) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * @param string $name
     * @return TableColumnStub
     */
    public function column($name) {
        $found = NULL;
        foreach ($this->stub as $colName => $column) {
            if (strtolower($colName) == strtolower($name)) {
                return $column;
            }
        }

        $stub = new TableColumnStub();
        $stub->name = $name;
        $stub->modifyFields[] = '*'; // = CREATE

        $this->stub[ $name ] = $stub;
        return $this->stub[$name];
    }

    /**
     * @return TableColumnStub[]
     */
    public function columns() {
        return $this->stub;
    }

    /**
     * @param string $indexName
     * @return TableIndexStub
     */
    public function index($indexName) {
        if (!isset($this->indexes[$indexName])) {
            $index = new TableIndexStub();
            $index->name = $indexName;
            $index->modifyFields[] = '*';

            $this->indexes[ $indexName ] = $index;

        }

        return $this->indexes[ $indexName ];
    }

    protected function makeSqlProp(TableColumnStub $stub) {
        if ($stub->type != TableColumnStub::TYPE_TEXT && empty($stub->length)) {
            throw new \Exception($stub->name . " need SET length");
        }

        $sql = sprintf(
            " %s%s",
                $stub->type,
            empty($stub->length) ? "" : "(" . $stub->length . ") "
            );

        if (!$stub->allowNull) {
            $sql .= " NOT NULL ";
        }

        if ($stub->unsigned) {
            $sql .= " UNSIGNED ";
        }

        if ($stub->defaultValue !== NULL && $stub->type != TableColumnStub::TYPE_TEXT) {
            $value = $stub->defaultValue;
            if (in_array($stub->type, TableColumnStub::$stringTypes)) { //字符串类型时 我们包裹一下
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

        if ($this->isCreateMode) {
            $cols = [];
            foreach ($this->stub as $key => $stub) {
                $cols[] = "`" . $key . "`" . $this->makeSqlProp($stub);
                if ($stub->isPrimary) {
                    $pk[] = $stub->name;
                }
            }

            if (!empty($pk)) {
                $cols[] = 'PRIMARY KEY (' . implode(",", $pk) . ")";
            }

            $sqls[] = sprintf('CREATE TABLE %s (%s) COMMENT = ""', $this->tableName, implode(",\n", $cols));
        } else {
            $isModifyPK = FALSE;

            /** @var TableColumnStub $stub */
            foreach ($this->stub as $stub) {
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
                if ($stub->isPrimary) {
                    $pk[] = $stub->name;
                }

                if (in_array('isPrimary', $stub->modifyFields)) {
                    $isModifyPK = TRUE;
                }

                $sql .= $this->makeSqlProp($stub);
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
            TableIndexStub::TYPE_NORMAL => 'INDEX',
            TableIndexStub::TYPE_UNIQUE => 'UNIQUE',
            TableIndexStub::TYPE_FULLTEXT => 'FULLTEXT'
        ];

        /**
         * @var string $indexName
         * @var TableIndexStub $index
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

    public function execute($onlyReturn = FALSE) {
        $extraSqls = [];
        if ($this->doDropTable) {
            $extraSqls[] = 'DROP TABLE `' . $this->tableName . '`';
        }

        $sqls = array_merge($extraSqls, $this->getStubSQL(), $this->getIndexSQL());
        if ($onlyReturn) return $sqls;

        $this->connection->beginTransaction();
        foreach ($sqls as $sql) {
            try {
                $this->connection->query($sql);
            } catch (DBException $e) {
                $this->connection->rollback();
                throw $e;
            }
        }

        $this->connection->commit();
    }

    public function dropTable() {
        $this->doDropTable = TRUE;

        return $this;
    }

    public function loadModelClass($className) {
        $reflector = new \ReflectionClass($className);
        $class = new $className();
        $result = [];

        $map = [];
        if ($class instanceof DatabaseModel) {
            $map = array_flip($class->columnMap());
        }

        import('core.external.DocParser');
        foreach($reflector->getProperties() as $prop) {
            if (($prop->isProtected() || $prop->isPublic()) && $prop->getName()[0] != '_') {
                if ($prop->isStatic()) continue;

                $parser = new \DocParser();
                $values = $parser->parse($prop->getDocComment());
                if ($prop->isDefault()) {
                    $prop->setAccessible(TRUE);
                    $default = $prop->getValue($class);

                    if ($default === TIMESTAMP) {
                        $default = NULL;
                    }

                    $values['default'] = $default;
                }

                $name = $prop->getName();
                if (isset($map[$name])) {
                    $name = $map[$name];
                }

                $result[ $name ] = $values;
            }
        }

        $varMap = [
            'int' => TableColumnStub::TYPE_INT
        ];

        foreach ($result as $key => $c) {
            $f = $this->column($key);

            if ($c['default'] !== NULL) $f->setDefault( $c['default'] );
            if (!empty($c['description'])) $f->setRemark($c['description']);
            if (isset($c['nullable'])) $f->nullable(TRUE);
            if (isset($c['unsigned'])) $f->unsigned();
            if (isset($c['zerofill'])) $f->zerofill();

            if (!empty($c['type'])) {
                $f->setType( $c['type'] );
            } else {
                if (in_array("*", $f->modifyFields)) {
                    if (isset($varMap[ $c['var'] ])) $f->setType( $varMap[$c['var']] );
                }
            }

            if (isset($c['length'])) $f->setLength( $c['length'] );
            if (isset($c['primary'])) $f->primary();
        }
    }

}
