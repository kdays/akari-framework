<?php
namespace Akari\model;

use Akari\system\db\DBAgentFactory;

!defined("AKARI_PATH") && exit;

Class DatabaseModel extends Model{
    /**
     * @var \Akari\system\db\DBAgent
     */
    public $db;

	protected function __construct(){
		$this->db = DBAgentFactory::getDBAgent();
	}

    public function _getDBAgent($name = 'default') {
        return DBAgentFactory::getDBAgent($name);
    }

    public static $m = [];

    /**
     * 单例公共调用，不应在action中调用本方法
     * @return self
     */
    public static function _instance() {
        $class = get_called_class();
        if (!isset(self::$m[$class])) {
            self::$m[ $class ] = new $class;
        }

        return self::$m[ $class ];
    }
}