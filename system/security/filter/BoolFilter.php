<?php

namespace Akari\system\security\filter;

class BoolFilter extends BaseFilter {

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

        if ($data || $data === '0' || strtolower($data) === 'true') {
            return true;
        }

        return false;
    }

}