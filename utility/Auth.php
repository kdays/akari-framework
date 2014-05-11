<?php
/**
 * 用户权限控制工具
 *
 **/
Class Auth{
	private $data = array();
	private $u = array();

	public function setGroup($groupId, $permissionLst){
		$this->data[$groupId] = $permissionLst;
	}

	public function setUser($userId, $groupIds){
		if(!is_array($groupIds))	$groupIds = array($groupIds);
		$p = array();
		foreach($groupIds as $gid){
			$p = array_merge($p, $this->getGroup($gid));
		}
		$p = array_unique($p);

		$this->u[$userId] = $p;
	}

	public function getUser($userId){
		return $this->u[$userId];
	}

	public function getGroup($groupId){
		return $this->data[$groupId];
	}

	public function check($permissionId, $uid){
		if(!$this->u[$uid])	return FALSE;
		if(in_array($permissionId, $this->u[$uid])){
			return TRUE;
		}

		return FALSE;
	}
}