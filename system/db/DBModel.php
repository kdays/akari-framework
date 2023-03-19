<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2019-03-03
 * Time: 02:33
 */

namespace Akari\system\db;

use Illuminate\Database\Eloquent\Model;

abstract class DBModel extends Model {

    public static function findFirst($conditions) {
        $builder = static::query();

        foreach ($conditions['conditions'] as $key => $value) {
            $builder->where($key, '=', $value);
        }

        return $builder->first();
    }

    public static function findById($id) {
        $builder = static::query();
        return $builder->find($id);
    }

}
