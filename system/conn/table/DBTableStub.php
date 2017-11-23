<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/11/22
 * Time: 下午3:46
 */

namespace Akari\system\conn\table;


use Akari\system\conn\DBException;

class DBTableStub {

    const TYPE_VARCHAR = 'varchar';
    const TYPE_TEXT = 'text';
    const TYPE_INT = 'int';
    const TYPE_TINYINT = 'tinyint';

    public static $stringTypes = [
        self::TYPE_VARCHAR,
        self::TYPE_TEXT
    ];

    const EXTRA_AUTO_INCREMENT = 'AUTO_INCREMENT';

    public $name;

    public $oldName;

    public $length;

    public $type = self::TYPE_TEXT;

    public $defaultValue = NULL;

    public $remark = '';

    public $isPrimary = false;

    public $allowNull = false;

    public $unsigned = false;

    public $zerofill = false;

    public $extra = '';

    public $modifyFields = [];

    public function _handleModify(string $fieldName, $value) {
        if ($this->$fieldName != $value) {
            $this->modifyFields[] = $fieldName;
        }

        $this->$fieldName = $value;
        return $this;
    }

    public function setLength(int $length) {
        return $this->_handleModify('length', $length);
    }

    public function setType(string $type) {
        return $this->_handleModify('type', $type);
    }

    public function setDefault(string $defaultValue) {
        return $this->_handleModify('defaultValue', $defaultValue);
    }

    public function setRemark(string $remark) {
        return $this->_handleModify('remark', $remark);
    }

    public function primary() {
        return $this->_handleModify('isPrimary', true);
    }

    public function nullable($to = true) {
        return $this->_handleModify('allowNull', !!$to);
    }

    public function zeroFill($to = true) {
        if (in_array($this->type, self::$stringTypes)) {
            throw new DBException("Field ". $this->name . " is TEXT type. can't SET zerofill");
        }
        return $this->_handleModify('zerofill', !!$to);
    }

    public function unsigned($to = true) {
        if (in_array($this->type, self::$stringTypes)) {
            throw new DBException("Field ". $this->name . " is TEXT type. can't SET unsigned");
        }
        return $this->_handleModify('unsigned', !!$to);
    }

    public function setAutoIncr() {
        return $this->_handleModify('extra', self::EXTRA_AUTO_INCREMENT);
    }

    public function modifyName(string $toName) {
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


}