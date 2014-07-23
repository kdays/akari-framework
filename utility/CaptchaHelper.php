<?php
namespace Akari\utility;

Class CaptchaHelper {
    public static $h;
    public static function getInstance() {
        if (isset(self::$h)) {
            self::$h = new self();
        }

        return self::$h;
    }
    public $code = NULL;
    public $im = NULL;

    public $dict = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ123456789';
    public $width = 120;
    public $height = 45;
    public $size = 20;
    public $fx = 18;
    public $snow = 200;
    public $font = NULL;

    public function setDict($words) {
        $this->dict = $words;
        return $this;
    }

    public function setWidth($width) {
        $this->width = $width;
        return $this;
    }

    public function setHeight($height) {
        $this->height = $height;
        return $this;
    }

    public function setSize($size) {
        $this->size = $size;
        return $this;
    }

    public function setFont($fontPath) {
        $this->font = $fontPath;
        return $this;
    }

    public function makeSnowBackground($im) {
        for($i = 1; $i < $this->snow; $i++){
            $x = mt_rand(1, $this->width - 9);
            $y = mt_rand(1, $this->height - 9);
            $color = imagecolorallocate($im,mt_rand(200,255),mt_rand(200,255),mt_rand(200,255));

            imagechar($im, 1, $x, $y, "*", $color);
        }

        return $im;
    }

    public function getRndStr($length = 4) {
        $dict = $this->dict;
        $code = '';

        for($i = 0; $i < $length; $i++){
            $code .= $dict[ array_rand($dict) ];
        }

        return $code;
    }

    /**
     * @param int $length
     * @return $this
     */
    public function create($length = 4) {
        $str = $this->getRndStr($length);
        $im = imagecreatetruecolor($this->width, $this->height);

        $white = imagecolorallocate($im,255,255,255); //第一次调用设置背景色
        $black = imagecolorallocate($im,0,0,0); //边框颜色

        $im = $this->makeSnowBackground($im);
        for($i = 0;$i < count($str);$i++){
            $x = 12 + $i * ($this->width - 15)/4;
            $y = $this->height - $this->fx + mt_rand(2, $this->height / 3);
            $color = imagecolorallocate($im,mt_rand(0,225),mt_rand(0,150),mt_rand(0,225));

            imagettftext($im, $this->size, 0, $x, $y, $color, $this->font, $str[$i]);
        }

        $this->code = $str;
        $this->im = $im;

        return $this;
    }

    public function getCode() {
        return $this->code;
    }

    public function getImage() {
        return $this->im;
    }

}