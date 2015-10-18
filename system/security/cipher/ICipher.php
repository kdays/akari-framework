<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:07
 */

namespace Akari\system\security\cipher;

interface ICipher {

    /**
     * @param string $text
     * @return mixed
     */
    public function encrypt($text);

    /**
     * @param string $text
     * @return mixed
     */
    public function decrypt($text);

}