<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/11/22
 * Time: 下午3:46
 */

namespace Akari\system\conn\table;


use Akari\model\DatabaseModel;
use Akari\system\conn\DBConnection;
use Akari\system\conn\DBException;

class DBTableFactory {

    protected $tableName;

    protected $stub = [];
    protected $index;

    protected $connection;
    protected $isCreateMode;

    public function __construct(DBConnection $DBConnection, string $tableName) {
        $this->tableName = $tableName;
        $this->connection = $DBConnection;

        // 查询是否有结构 我们先获得数据表结构
        $cols = $DBConnection->fetch("SELECT * FROM information_schema.columns WHERE `table_schema` = :db_name and `table_name` = :table_name", [
            'db_name' => $DBConnection->getDatabaseName(),
            'table_name' => $tableName
        ]);

        foreach ($cols as $col) {
            $stub = new DBTableStub();
            $stub->name = $col['COLUMN_NAME'];
            $stub->type = $col['DATA_TYPE'];
            if (!empty($col['CHARACTER_MAXIMUM_LENGTH'])) {
                $stub->length = (int)$col['CHARACTER_MAXIMUM_LENGTH'];
            } else {
                preg_match('/(\d+)/', $col['COLUMN_TYPE'], $matches);
                $stub->length = (int)$matches[0];
            }
            $stub->remark = $col['COLUMN_COMMENT'];
            $stub->isPrimary = $col['COLUMN_KEY'] == 'PRI';
            $stub->extra = $col['EXTRA'];
            $stub->oldName = $stub->name;
            $stub->defaultValue = $col['COLUMN_DEFAULT'];

            $stub->allowNull = $col['IS_NULLABLE'] != 'NO';
            if (in_string($col['COLUMN_TYPE'],'unsigned')) {
                $stub->unsigned = true;
            }

            if (in_string($col['COLUMN_TYPE'], 'zerofill')) {
                $stub->zerofill = true;
            }

            $this->stub[ $stub->name ] = $stub;
        }

        $this->isCreateMode = empty($this->stub);
    }

    /**
     * @param string $name
     * @return DBTableStub
     */
    public function field(string $name) {
        if (!isset($this->stub[$name])) {
            $stub = new DBTableStub();
            $stub->name = $name;
            $stub->modifyFields[] = '*'; // = CREATE

            $this->stub[ $name ] = $stub;
        }

        return $this->stub[$name];
    }

    /**
     * @return DBTableStub[]
     */
    public function fields() {
        return $this->stub;
    }

    public function createIndex(...$args) {
        if (count($args) < 1) {
            return false;
        }


    }

    private function makeSqlProp(DBTableStub $stub) {
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

        if ($stub->defaultValue !== NULL && $stub->type != DBTableStub::TYPE_TEXT) {
            $value = $stub->defaultValue;
            if (in_array($stub->type, DBTableStub::$stringTypes)) { //字符串类型时 我们包裹一下
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

    public function execute($onlyReturn = false) {
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
            $isModifyPK = false;

            /** @var DBTableStub $stub */
            foreach ($this->stub as $stub) {
                if (empty($stub->modifyFields)) {
                    continue;
                }

                $sql = sprintf('ALTER TABLE `%s`', $this->tableName);
                if (in_array('*', $stub->modifyFields)) {
                    $sql .= sprintf(" ADD COLUMN `%s`", $stub->name);
                } else {
                    $sql .= sprintf(" CHANGE COLUMN `%s` `%s`", $stub->oldName, $stub->name);
                }

                // 处理Parimary Key
                if ($stub->isPrimary) {
                    $pk[] = $stub->name;
                }

                if (in_array('isPrimary', $stub->modifyFields)) {
                    $isModifyPK = true;
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

    public function loadModelClass(string $className) {
        $reflector = new \ReflectionClass($className);
        $class = new $className();
        $result = [];

        $map = [];
        if ($class instanceof DatabaseModel) {
            $map = array_flip($class->columnMap());
        }

        import('core.external.DocParser');
        foreach($reflector->getProperties() as $prop) {
            if ($prop->isProtected() && $prop->getName()[0] != '_') {
                $parser = new \DocParser();
                $values = $parser->parse($prop->getDocComment());
                if ($prop->isDefault()) {
                    $prop->setAccessible(true);
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
            'int' => DBTableStub::TYPE_INT
        ];

        foreach ($result as $key => $c) {
            $f = $this->field($key);

            if ($c['default'] !== NULL) {
                $f->setDefault( $c['default'] );
            }

            if (!empty($c['description'])) {
                $f->setRemark($c['description']);
            }

            if (isset($c['nullable'])) {
                $f->nullable(true);
            }

            if (isset($c['unsigned'])) {
                $f->unsigned();
            }

            if (isset($c['zerofill'])) {
                $f->zerofill();
            }

            if (!empty($c['type'])) {
                $f->setType( $c['type'] );
            } else {
                if (in_array("*", $f->modifyFields)) {
                    if (isset($varMap[ $c['var'] ])) $f->setType( $varMap[$c['var']] );
                }
            }

            if (isset($c['length'])) {
                $f->setLength( $c['length'] );
            }

            if (isset($c['primary'])) {
                $f->primary();
            }
        }
    }

}