<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/11/23
 * Time: 下午3:41
 */

namespace Akari\system\conn\table;


class DBTableIndex {

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

    public function _handleModify(string $fieldName, $value) {
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