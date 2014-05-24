<?php
!defined("AKARI_PATH") && exit;

Class DatabaseModel extends Model{
	public $db;

	public function __construct(){
		$this->db = DBAgentFactory::getDBAgent();
	}
}