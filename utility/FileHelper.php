<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/10/1
 * Time: 上午11:12
 */

namespace Akari\utility;


class FileHelper {

    public static function read($filename, $method = 'rb'){
        if(function_exists("file_get_contents")){
            return file_get_contents($filename);
        }
        
        $data = '';
        if ($handle = @fopen($filename,$method)) {
            flock($handle,LOCK_SH);
            $data = @fread($handle,filesize($filename));
            fclose($handle);
        }

        return $data;
    }

    public static function write($fileName, $data, $method = 'rb+', $ifChmod = true){
        $baseDir = dirname($fileName);
        self::createDir($baseDir);

        touch($fileName);
        $handle = fopen($fileName, $method);
        flock($handle, LOCK_EX);
        fwrite($handle, $data);
        $method == 'rb+' && ftruncate($handle, strlen($data));
        fclose($handle);
        $ifChmod && @chmod($fileName, 0777);
    }
    
    public static function remove($fileName) {
        unlink($fileName);
    }
    
    public static function moveFile($target, $source){
        self::createDir(dirname($target));
        
        if (rename($source,$target)) {
            @chmod($target,0777);
            return true;
        } elseif (@copy($source,$target)) {
            @chmod($target,0777);
            unlink($source);
            return true;
        } elseif (is_readable($source)) {
            self::write($target, self::read($source));
            if (file_exists($target)) {
                @chmod($target,0777);
                unlink($source);
                return true;
            }
        }
        
        return false;
    }
    
    public static function createDir($path, $index = false){
        if(is_dir($path))	return ;
        self::createDir(dirname($path), $index);

        @mkdir($path);
        @chmod($path,0777);
        if(!$index){
            @fclose(@fopen($path.'/index.html','w'));
            @chmod($path.'/index.html',0777);
        }
    }
    
    public static function removeDir($path){
        if(!is_dir($path))  return false;

        if(rmdir($path) == false){
            if(!$dp = opendir($path))   return false;

            while(($fp = readdir($dp)) !== false){
                if($fp == "." || $fp == "..")   continue;
                if(is_dir("$path/$fp")){
                    self::deleteDir("$path/$fp");
                }else{
                    unlink("$path/$fp");
                }
            }

            closedir($dp);
            rmdir($path);
        }
    }
    
}