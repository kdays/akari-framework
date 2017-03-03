<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/27
 * Time: 下午7:06
 */

namespace Akari\model;

use Akari\utility\TextHelper;
use Akari\config\DbAgentConfig;
use Akari\system\exception\AkariException;

abstract class DatabaseModel extends Model implements \ArrayAccess, \JsonSerializable {

    public static function model() {
        return new static();
    }

    /**
     * [DatabaseKey -> ModelKey]
     * @return array
     */
    abstract public function columnMap();

    /**
     * 获得表名,如果没有的话则从class中启发
     * 
     * @return string
     */
    public function tableName() {
        $cls = explode(NAMESPACE_SEPARATOR, get_called_class());
        $tName = array_pop($cls);
        $tName[0] = strtolower($tName[0]);

        return TextHelper::snakeCase($tName);
    }

    /**
     * 返回数组
     * 
     * @param array $except 例外列表
     * @param bool $useModelKey 是否使用模型键
     * @param bool $useGetter 是否自动调用模型的get方法
     * @param bool $allowExceptObject 设置TRUE时,你在columnMap中未设置,但模型中用数组方法设置的项目也会返回
     * @return array
     */
    public function toArray($except = [], $useModelKey = FALSE, $useGetter = FALSE, $allowExceptObject = FALSE) {
        $result = [];

        $columnMap = $this->columnMap();
        $flipMap = array_flip($columnMap);

        if  ($allowExceptObject) {
            foreach ($this as $k => $v) {
                if (!isset($flipMap[$k])) $columnMap[$k] = $k;
            }
        }

        foreach ($columnMap as $dbField => $modelField) {
            if (in_array($modelField, $except)) {
                continue;
            }

            $result[$useModelKey ? $modelField : $dbField] = $useGetter ? $this[$modelField] : $this->$modelField;
        }

        unset($result['_dependencyInjector']);

        return $result;
    }

    public static function toModel(array $in) {
        $model = new static();
        $columnMap = $model->columnMap();

        foreach ($in as $inKey => $inValue) {
            if (isset($columnMap[$inKey])) {
                $model->{$columnMap[$inKey]} = $inValue;
            } else {
                $model[ $inKey ] = $inValue;
            }
        }

        return $model;
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            throw new AkariException(__CLASS__ . " Model offset is NULL");
        }

        $magicName = $offset;
        $magicName[0] = strtoupper($magicName[0]);

        if (method_exists($this, 'set' . $magicName)) {
            $f = "set" . $magicName;
            $this->$f($value);
        } else {
            $this->$offset = $value;
        }
    }

    public function offsetExists($offset) {
        return isset($this->$offset);
    }

    public function offsetUnset($offset) {
        $this->$offset = NULL;
    }

    public function offsetGet($offset) {
        $magicName = $offset;
        $magicName[0] = strtoupper($magicName[0]);

        if (method_exists($this, 'get' . $magicName)) {
            $f = 'get' . $magicName;

            return $this->$f();
        }

        return isset($this->$offset) ? $this->$offset : NULL;
    }

    /**
     * json_encode时方法,如果有字段不希望json_encode时被重写,可自行实现
     *
     * @return array
     */
    public function jsonSerialize() {
        return $this->toArray([],
            DbAgentConfig::JSON_AUTO_MODEL_KEY,
            DbAgentConfig::JSON_AUTO_USE_GETTER,
            DbAgentConfig::JSON_AUTO_ARRAY_EXCEPT);
    }
}
