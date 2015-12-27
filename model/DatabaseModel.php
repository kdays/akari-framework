<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/12/27
 * Time: 下午7:06
 */

namespace Akari\model;


abstract class DatabaseModel extends Model implements \ArrayAccess{

    public static function model() {
        return new static();
    }

    /**
     * @return array
     */
    abstract  public function columnMap();

    public function toArray($except = [], $useModelKey = False) {
        $result = [];
        foreach ($this->columnMap() as $dbField => $modelField) {
            if (in_array($modelField, $except)) {
                continue;
            }
            $result[$useModelKey ? $modelField : $dbField] = $this->$modelField;
        }
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
            throw new \Exception("Model offsetSet error");
        } else {
            $offsetFunc = $offset;
            $offsetFunc[0] = strtoupper($offsetFunc[0]);

            if (method_exists($this, 'set'. $offsetFunc)) {
                $f = "set". $offsetFunc;
                $this->$f($value);
            } else {
                $this->$offset = $value;
            }
        }
    }

    public function offsetExists($offset) {
        return isset($this->$offset);
    }

    public function offsetUnset($offset) {
        $this->$offset = NULL;
    }

    public function offsetGet($offset) {
        $offsetFunc = $offset;
        $offsetFunc[0] = strtoupper($offsetFunc[0]);
        if (method_exists($this, 'get'. $offsetFunc)) {
            $f = 'get'. $offsetFunc;
            return $this->$f();
        }

        return isset($this->$offset) ? $this->$offset : null;
    }


}