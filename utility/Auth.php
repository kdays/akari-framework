<?php
namespace Akari\utility;
use Akari\Context;

/**
 * 用户权限控制工具
 *
 **/
Class Auth{
	private $data = array();
	private $u = array();

    public static $a;
    public static function getInstance() {
        if (!isset(self::$a)) {
            self::$a = new self();
        }

        return self::$a;
    }

    protected function __construct() {
        $this->data = C("AuthPermission", NULL, []);
    }

	/**
	 * 设置组别权限
	 * 
	 * @param int $groupId 组别id
	 * @param array $permissionLst 权限列表
	 */
	public function setGroup($groupId, $permissionLst){
		$this->data[$groupId] = $permissionLst;
	}

	/**
	 * 对用户设定组别权限
	 * 
	 * @param int $userId 用户id
	 * @param array $groupIds 组别id列表
	 */
	public function setUser($userId, $groupIds){
		if(!is_array($groupIds))	$groupIds = array($groupIds);
		$p = array();
		foreach($groupIds as $gid){
			$p = array_merge($p, $this->getGroup($gid));
		}
		$p = array_unique($p);

		$this->u[$userId] = $p;
	}

	/**
	 * 获得用户权限列表
	 * 
	 * @param int $userId 用户id
	 * @return array
	 */
	public function getUser($userId){
		return $this->u[$userId];
	}

    /**
     * 获得组别下的权限列表
     *
     * @param int $groupId 组别id
     * @return array
     */
	public function getGroup($groupId){
        $list = [];

        if (!isset($this->data[$groupId])) {
            return [];
        }

        foreach ($this->data[$groupId] as $value) {
            if (strpos($value, 'P:') !== FALSE) {
                $subGroupName = str_replace('P:', '', $value);
                foreach ($this->getGroup($subGroupName) as $subGroupPermission) {
                    $list[] = $subGroupPermission;
                }
            } elseif (strpos($value, "D:") !== false) {
                $readyKey = str_replace("D:", '', $value);

                if (in_array($readyKey, $list)) {
                    $pos = array_search($readyKey, $list);
                    var_dump($pos);
                    $list = array_slice($list, $pos - 1, sizeof($list) - 1);
                }
            } else {
                $list[] = $value;
            }
        }

        return $list;
	}
	
	/**
	 * 检查权限
	 * 
	 * @param string $permissionId 权限名
	 * @param int $uid 用户uid
	 * @return boolean
	 */
	public function check($permissionId, $uid){
		if(!$this->u[$uid])	return FALSE;
		if(in_array($permissionId, $this->u[$uid])){
			return TRUE;
		}

		return FALSE;
	}
}