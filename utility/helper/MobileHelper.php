<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/27
 * Time: 14:06
 */

namespace Akari\utility\helper;

trait MobileHelper {

    private $_userAgent;
    protected function __construct() {
        $this->_userAgent = $_SERVER['HTTP_USER_AGENT'];
    }

    /**
     * 是否是iphone
     * @return bool
     */
    public function isIPhone(){
        return (preg_match('/ipod/i', $this->_userAgent) ||
            preg_match('/iphone/i', $this->_userAgent));
    }

    /**
     * 是否是ipad
     * @return int
     */
    public function isIPad() {
        return !!(preg_match('/ipad/i', $this->_userAgent));
    }

    /**
     * 判断是否是微信的
     * @return bool
     */
    public function isWeixin() {
        return (preg_match('/MicroMessenger/i', $this->_userAgent) ||
            preg_match('/Window Phone/i', $this->_userAgent));
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
            if (preg_match("/$value/i", $this->_userAgent)) {
                return true;
            }
        }

        return false;
    }

}