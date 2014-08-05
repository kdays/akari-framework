<?php
namespace Akari\system\http;

!defined("AKARI_PATH") && exit;

Class MobileDevice{
	private $userAgent;

	public function __contruct(){
		$this->userAgent = $_SERVER['HTTP_USER_AGENT'];
	}

	protected static $m;
	public static function getInstance(){
		if(!isset(self::$m)){
			self::$m = new self();
		}
		return self::$m;
	}

    /**
     * 是否是iphone
     * @return bool
     */
    public function isIPhone(){
		return (preg_match('/ipod/i', $this->userAgent) ||
            preg_match('/iphone/i', $this->userAgent));
	}

    /**
     * 是否是ipad
     * @return int
     */
    public function isIPad() {
        return !!(preg_match('/ipad/i', $this->userAgent));
    }

    /**
     * 判断是否是微信的
     * @return bool
     */
    public function isWeixin() {
        return (preg_match('/MicroMessenger/i', $this->userAgent) ||
            preg_match('/Window Phone/i', $this->userAgent));
    }

    /**
     * 是否是移动设备 (不含ipad)
     * @return bool
     */
    public function isMobile(){
		if($this->isIPhone()) {
            return true;
        }

        $keyword = ["ucweb", "Windows Phone", "android", "opera mini", "blackberry"];
        foreach ($keyword as $value) {
            if (preg_match("/$value/i", $this->userAgent)) {
                return true;
            }
        }

		return false;
	}
}