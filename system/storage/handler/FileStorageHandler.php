<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 2017/12/7
 * Time: 下午6:10
 */

namespace Akari\system\storage\handler;


use Akari\Core;
use Akari\exception\AkariException;
use Akari\system\util\TextUtil;

class FileStorageHandler extends BaseStorageHandler implements IStorageHandler {

    /**
     * @param string $path
     * @param resource|mixed $content
     * @return mixed
     * @throws AkariException
     */
    public function put(string $path, $content) {
        $savePath = $this->formatPath($path);

        if (!is_dir(dirname($savePath))) {
            mkdir(dirname($savePath), 0777, TRUE);
        }
        return file_put_contents($savePath, $content);
    }

    /**
     * @param string $path
     * @return mixed
     * @throws AkariException
     */
    public function get(string $path) {
        $savePath = $this->formatPath($path);
        return file_get_contents($savePath);
    }

    /**
     * @param string $path
     * @return bool
     * @throws AkariException
     */
    public function exists(string $path) {
        // TODO: Implement exists() method.
        $savePath = $this->formatPath($path);
        return file_exists($savePath);
    }

    /**
     * @param array|string $path 是否删除
     * @return mixed
     * @throws AkariException
     */
    public function delete($path) {
        // TODO: Implement delete() method.
        if ($this->exists($path)) {
            $savePath = $this->formatPath($path);

            return unlink($savePath);
        }

        return FALSE;
    }

    /**
     * @param string $path
     * @param array $options
     * @return string
     */
    public function toUrl(string $path, array $options = []) {
        // TODO: Implement toUrl() method.
        $url = $this->config['host'] . $path;
        return str_replace(['//', ':/'], ['/', '://'], $url);
    }

    /**
     * @param string $path
     * @return int
     * @throws AkariException
     */
    public function size(string $path) {
        // TODO: Implement size() method.
        $savePath = $this->formatPath($path);
        return filesize($savePath);
    }

    protected function formatPath(string $path) {
        $baseDir = $this->config['baseDir'];
        $baseDir = $baseDir[0] == '/' ? $baseDir : Core::$appDir . DIRECTORY_SEPARATOR . $baseDir;
        $baseDir = str_replace('//', '/', $baseDir);

        $path = $baseDir . $path;
        $path = str_replace('//', '/', $path);

        if (!TextUtil::exists(dirname($path), dirname($baseDir))) {
            throw new AkariException("BasePath Error");
        }

        return $path;
    }

    public function items(string $dirName, array $options = []) {
        $baseDir = $this->config['baseDir'];
        $baseDir = $baseDir[0] == '/' ? $baseDir : Core::$appDir . DIRECTORY_SEPARATOR . $baseDir;
        $baseDir = str_replace('//', '/', $baseDir);

        $targetName = $baseDir . DIRECTORY_SEPARATOR . $dirName;
        $targetName = str_replace('//', '/', $targetName);

        // 处理dirName的情况
        $files = scandir($targetName);
        $result = [];
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
               continue;
            }

            $result[] = $dirName . $file;
        }

        return $result;
    }
}
