<?php
/**
 * Created by PhpStorm.
 * User: kdays
 * Date: 15/10/1
 * Time: 上午11:12
 */

namespace Akari\utility;


use Akari\Context;

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
    
    public static function copyFile($source, $target) {
        $baseDir = dirname($target);
        self::createDir($baseDir);
        
        copy($source, $target);
    }
    
    public static function moveFile($target, $source){
        if (!$target) {
            return false;
        }
        
        if (!is_dir(dirname($target))) {
            self::createDir(dirname($target));
        }
        
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
    
    public static function createDir($path, $makeIndex = false){
        if (is_dir($path)) {
            return ;
        }
        
        self::createDir(dirname($path), $makeIndex);

        mkdir($path, 0777);
        if($makeIndex){
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
                    self::removeDir("$path/$fp");
                }else{
                    unlink("$path/$fp");
                }
            }

            closedir($dp);
            rmdir($path);
        }
    }
    
    public static function copyDir($src, $dst) {
        if (is_dir($src)) {
            mkdir($dst);
            
            $files = scandir($src);
            foreach ($files as $file){
                if ($file == "." || $file == "..") continue;
                self::copyDir($src. DIRECTORY_SEPARATOR. $file, $dst. DIRECTORY_SEPARATOR. $file);
            }
        } elseif (file_exists($src)) {
            copy($src, $dst);
        }
    }
    
    public static function getUploadPath($fn) {
        return Context::$appBasePath. DIRECTORY_SEPARATOR. Context::$appConfig->uploadDir. DIRECTORY_SEPARATOR. $fn;
    }

    public static function formatFileSize($size, $dec = 2){
        $a = array("B", "KB", "MB", "GB", "TB", "PB");
        $pos = 0;
        while ($size >= 1024) {
            $size /= 1024;
            $pos++;
        }

        return round($size, $dec)." ".$a[$pos];
    }
    
    public static function getFileExtension($fn) {
        $exts = explode(".", $fn);
        $ext = end($exts);
        
        return strtolower($ext);
    }
}