<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/12/7
 * Time: 下午6:04
 */

namespace Akari\system\storage\handler;


interface IStorageHandler {

    /**
     * @param string $path
     * @param resource|mixed $content
     * @param string|null $mode 写入模式 see StorageDisk::PUT_MODE_*
     * @return mixed
     */
    public function put(string $path, $content, $mode = NULL);

    /**
     * @param string $path
     * @return mixed
     */
    public function get(string $path);

    /**
     * @param string $path
     * @return bool
     */
    public function exists(string $path);

    /**
     * @param array|string $path 是否删除
     * @return mixed
     */
    public function delete($path);

    /**
     * @param string $path
     * @param array $options
     * @return string
     */
    public function toUrl(string $path, array $options = []);

    /**
     * @param string $path
     * @return int
     */
    public function size(string $path);

    public function items(string $dirName, array $options = []);


}
