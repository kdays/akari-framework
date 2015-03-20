<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 14/12/28
 * Time: 23:06
 */

namespace Akari\system\cache;

use Akari\Context;
use Akari\utility\Benchmark;

Class FileCache extends ICache {

    protected $indexPath;
    protected $fileIndex = [];

    public function __construct($confId = 'default') {
        $opts = $this->getOption('file', $confId, [
            'path' => implode(DIRECTORY_SEPARATOR, [Context::$appBasePath, 'runtime', 'cache', '']),
            'index' => 'data.json',
            'prefix' => ''
        ]);


        $this->indexPath = $opts['path'].$opts['index'];
        $this->options = $opts;
        if(!file_exists($this->indexPath)){
            file_put_contents($this->indexPath, json_encode($this->fileIndex));
        }else{
            $this->fileIndex = json_decode(file_get_contents($this->indexPath), TRUE);
        }

        $isFoundRemove = false;
        foreach($this->fileIndex as $key => $value){
            if($value['expire'] > 0 && $value['expire'] < time()){
                $isFoundRemove = true;
                $this->remove($key, false);
            }
        }

        if($isFoundRemove)	$this->update();
    }

    public function getPath($key = ''){
        $hash = md5(uniqid());
        return $key."_".substr($hash, 6, 11);
    }

    /**
     * 删除文件缓存中的某个键
     *
     * @param string $key
     * @param bool $oper 是否立即生效，不立即生效的话后面需要用update()处理
     *
     * @return bool
     */
    public function remove($key, $oper = true){
        $key = $this->options['prefix'].$key;
        if(!isset($this->fileIndex[$key]))	return false;

        $data = $this->fileIndex[$key];
        if(file_exists($kPath = $this->options['path'].$data['f'])){
            CacheBenchmark::log(CacheBenchmark::ACTION_REMOVE);

            unlink($kPath);
        }
        unset($this->fileIndex[$key]);

        if($oper)	$this->update();
        return true;
    }

    public function set($key, $value, $expire = -1, $oper = true){
        $key = $this->options['prefix'].$key;
        if(isset($this->fileIndex[$key]))	$this->remove($key);

        $fileName = $this->getPath($key);
        $this->fileIndex[$key] = Array(
            'f' => $fileName,
            'expire' => ($expire>0) ? (time()+$expire) : $expire
        );

        CacheBenchmark::log(CacheBenchmark::ACTION_CREATE);
        file_put_contents($this->options['path'].$fileName, serialize($value));
        if($oper)	$this->update();
    }

    public function get($key, $defaultValue = false){
        $pKey = $this->options['prefix'].$key;

        if(isset($this->fileIndex[$pKey])){
            $now = $this->fileIndex[$pKey];
            $fpath = $this->options['path'].$now['f'];

            if(!file_exists($fpath)){
                $this->remove($key);
                return $defaultValue;
            }

            CacheBenchmark::log(CacheBenchmark::HIT);
            return unserialize(file_get_contents($fpath));
        }

        CacheBenchmark::log(CacheBenchmark::MISS);
        return $defaultValue;
    }

    public function update(){
        file_put_contents($this->indexPath, json_encode($this->fileIndex));
    }

}