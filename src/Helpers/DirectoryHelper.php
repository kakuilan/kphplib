<?php
/**
 * Copyright (c) 2016 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2016/12/25
 * Time: 16:59
 * Desc: -目录助手类
 */


namespace Lkk\Helpers;

class DirectoryHelper {

    /**
     * 创建深层目录
     * @param string $dir 路径
     * @param int $mode 权限模式
     * @return bool
     */
    public static function mkdirDeep($dir='', $mode=0766) {
        if(empty($dir)) return false;
        if(is_dir($dir) && chmod($dir, $mode)) {
            return true;
        } elseif (mkdir($dir, $mode, true)) {//第三个参数为true即可以创建多级目录
            return true;
        }

        return false;
    }


    /**
     * 批量改变目录模式(包括子目录和所属文件)
     * @param string $path 路径
     * @param int $filemode 文件模式
     * @param int $dirmode 目录模式
     */
    public static function chmodBatch($path='', $filemode=0766, $dirmode=0766){
        if (is_dir($path) ) {
            if (!chmod($path, $dirmode)) {
                return;
            }
            $dh = opendir($path);
            while (($file = readdir($dh)) !== false) {
                if($file != '.' && $file != '..') {
                    $fullpath = $path.'/'.$file;
                    self::chmodBatch($fullpath, $filemode,$dirmode);
                }
            }
            closedir($dh);
        } else {
            if (is_link($path)) {
                return;
            }
            if (!chmod($path, $filemode)) {
                return;
            }
        }
    }


    /**
     * 遍历路径的文件树
     * @param string $path 路径
     * @param string $type 获取类型:all-所有,dir-仅目录,file-仅文件
     * @param bool $recursive 是否递归
     * @return array
     */
    public static function getFileTree($path='', $type='all', $recursive=true){
        $path = rtrim($path, DS);
        $tree = [];
        // '{.,*}*' 相当于 '.*'(搜索.开头的隐藏文件)和'*'(搜索正常文件)
        foreach(glob($path.'/{.,*}*', GLOB_BRACE) as $single){
            if(is_dir($single)){
                $file = str_replace($path.'/', '', $single);
                if($file=='.' || $file=='..') continue;

                if($type!='file') $tree[] = $single;
                if($recursive) {
                    $tree = array_merge($tree, self::getFileTree($single, $type, $recursive));
                }
            }elseif($type!='dir'){
                $tree[] = $single;
            }
        }

        return $tree;
    }


    /**
     * 获取目录大小[字节]
     * @param string $path
     * @return int
     */
    public static function getDirSize($path='') {
        $size = 0;
        if(empty($path) || !is_dir($path)) return 0;
        $dh = @opendir($path); //比dir($path)快
        while(false != ($file = @readdir($dh)) ){
            if($file!='.' and $file!='..'){
                $fielpath = $path . DIRECTORY_SEPARATOR . $file;
                if(is_dir($fielpath)){
                    $size += self::getDirSize($fielpath);
                }else{
                    $size += filesize($fielpath);
                }
            }
        }
        @closedir($dh);
        return $size;
    }


    /**
     * 删除目录(目录下所有文件,包括本目录)
     * @param string $path
     * @return bool
     */
    public static function delDir($path='') {
        if(is_dir($path) && $hd = @opendir($path)){
            while(false != ($file = @readdir($hd)) ){
                if($file != '.' && $file != '..'){
                    $fielpath = $path . DIRECTORY_SEPARATOR . $file;
                    if(is_dir($fielpath)){
                        self::delDir($fielpath);
                    }else{
                        @unlink($fielpath);
                    }
                }
            }
            @closedir($hd);
            return @rmdir($path);
        }else{
            return false;
        }
    }


    /**
     * 清空目录(删除目录下所有文件,仅保留当前目录)
     * @param string $path
     * @return bool
     */
    public static function emptyDir($path='') {
        if(empty($path) || !is_dir($path)) return false;
        $tree = [];
        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path), \RecursiveIteratorIterator::SELF_FIRST);
        foreach ($objects as $single=>$object) {
            $checkNull = strpos(substr($single, -3), "/."); //检查文件是否 . 或者 ..
            if($checkNull !==false) continue;

            //先删除文件
            if(is_file($single)){
                @unlink($single);
            }else{
                $tree[] = $single;
            }
        }

        //再删除目录
        rsort($tree);
        foreach ($tree as $dir) {
            @rmdir($dir);
        }

        unset($objects,$object,$tree);
        return true;
    }


    /**
     * 拷贝目录
     * @param string $sourceDir 源目录
     * @param string $aimDir 目标目录
     * @return bool
     */
    public static function copyDir($sourceDir='',$aimDir=''){
        $succeed = true;
        if(!file_exists($aimDir)){
            if(!mkdir($aimDir,0777,true)){
                return false;
            }
        }
        $objDir = opendir($sourceDir);
        while(false !== ($fileName = readdir($objDir))){
            if(($fileName != ".") && ($fileName != "..")){
                if(!is_dir("$sourceDir/$fileName")){
                    if(!copy("$sourceDir/$fileName","$aimDir/$fileName")){
                        $succeed = false;
                        break;
                    }
                }
                else{
                    self::copyDir("$sourceDir/$fileName","$aimDir/$fileName");
                }
            }
        }
        closedir($objDir);
        return $succeed;
    }


    /**
     * 格式化路径字符串 [路径后面加/]
     * @param string $dir
     * @return string
     */
    public static function formatDir($dir='') {
        $dir = str_replace(["'",'#','=','`','$','%','&',';','|'], '', $dir);
        return rtrim(preg_replace('/(\/){2,}|(\\\){1,}/', '/', $dir), ' /　') . '/';
    }


}