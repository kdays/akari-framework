<?php
namespace Akari\model;

use Akari\system\db\DBAgentFactory;

!defined("AKARI_PATH") && exit;

Class DatabaseModel extends Model{
    /**
     * @var \Akari\system\db\DBAgent
     */
    public $db;

	public function __construct(){
		$this->db = DBAgentFactory::getDBAgent();
	}

    public static $m = [];
    public static function _instance() {
        $class = get_called_class();
        if (!isset(self::$m[$class])) {
            self::$m[ $class ] = new $class;
        }

        return self::$m[ $class ];
    }
}