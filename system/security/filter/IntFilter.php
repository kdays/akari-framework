<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2019/2/17
 * Time: 23:24
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
