<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/2/4
 * Time: 上午12:52
 */

namespace Akari\system\security\cipher;

class XxteaCipher extends Cipher{

    private $_secret;
    private $_useBase64 = TRUE;

    public function __construct(array $opts) {
        parent::__construct($opts);

        $this->_secret = md5($this->getOption('secret', 'Akari'));
        $this->_useBase64 = $this->getOption("base64", TRUE);
    }

    public function encrypt($text) {
        if (empty($text)) return "";
        $v = CipherUtil::str2long($text, TRUE);
        $k = CipherUtil::str2long($this->_secret, FALSE);

        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++) {
                $k[$i] = 0;
            }
        }
        $n = count($v) - 1;

        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = 0;
        while (0 < $q--) {
            $sum = CipherUtil::int32($sum + $delta);
            $e = $sum >> 2 & 3;
            for ($p = 0; $p < $n; $p++) {
                $y = $v[$p + 1];
                $mx = CipherUtil::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ CipherUtil::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $z = $v[$p] = CipherUtil::int32($v[$p] + $mx);
            }
            $y = $v[0];
            $mx = CipherUtil::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ CipherUtil::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z = $v[$n] = CipherUtil::int32($v[$n] + $mx);
        }

        $r = CipherUtil::long2str($v, FALSE);

        return $this->_useBase64 ? base64_encode($r) : $r;
    }

    public function decrypt($text) {
        if (empty($text)) return "";
        if ($this->_useBase64)  $text = base64_decode($text);

        $v = CipherUtil::str2long($text, FALSE);
        $k = CipherUtil::str2long($this->_secret, FALSE);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++) {
                $k[$i] = 0;
            }
        }
        $n = count($v) - 1;

        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = CipherUtil::int32($q * $delta);
        while ($sum != 0) {
            $e = $sum >> 2 & 3;
            for ($p = $n; $p > 0; $p--) {
                $z = $v[$p - 1];
                $mx = CipherUtil::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ CipherUtil::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $y = $v[$p] = CipherUtil::int32($v[$p] - $mx);
            }
            $z = $v[$n];
            $mx = CipherUtil::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ CipherUtil::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $y = $v[0] = CipherUtil::int32($v[0] - $mx);
            $sum = CipherUtil::int32($sum - $delta);
        }

        return CipherUtil::long2str($v, TRUE);
    }
}
