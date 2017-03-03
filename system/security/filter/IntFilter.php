<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 16/3/15
 * Time: 上午10:09
 */

namespace Akari\system\security\filter;

class IntFilter extends BaseFilter{

    /**
     * 过滤器实现方法
     *
     * @param mixed $data
     * @return mixed
     */
    public function filter($data) {
        if (is_array($data)) {
            foreach ($data as $k => $v) {
                $data[$k] = $this->filter($v);
            }

            return $data;
        }

        if (!is_numeric($data)) {
            return FALSE;
        }

        return (int) $data;
    }

}
