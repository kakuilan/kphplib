<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/7/14
 * Time: 16:28
 * Desc: -lkk 上传类
 */


namespace Lkk;

class LkkUpload {

    public $webDir = ''; //WEB目录

    public $result       = [
        'status' => false, //上传结果
        'info' => '', //提示信息
        'absolutePath' => '', //绝对路径
        'relativePath' => null, //相对WEB目录路径
        'name' => '', //保存的文件名
        'size' => 0, //文件大小,单位bit
    ];

    public $errorCode    = -1; //错误代码
    public $errorInfo    = [ //错误消息
        //系统错误消息
        '0' => '没有错误发生',
        '1' => '上传文件大小超出系统限制', //php.ini中upload_max_filesize
        '2' => '上传文件大小超出网页表单限制', //HTML表单中规定的MAX_FILE_SIZE
        '3' => '文件只有部分被上传',
        '4' => '没有文件被上传',
        '5' => '上传文件大小为0',
        '6' => '找不到临时文件夹',
        '7' => '文件写入失败',

        //自定义错误消息
        '-1' => '未知错误',
        '-2' => '未找到相应的文件域',
        '-3' => '文件大小超出允许范围:',
        '-4' => '文件类型不在允许范围:',
        '-5' => '未指定上传目录',
        '-6' => '创建目录失败',
        '-7' => '目录不可写',
        '-8' => '临时文件不存在',
        '-9' => '存在同名文件,取消上传',
        '-10' => '文件移动失败',
        '-11' => '文件内容可能不安全',
        '99'  => '上传成功',
    ];

    //默认参数
    private $savePath    = null; //文件保存目录
    private $allowType   = ['rar','zip','7z','txt','doc','docx','xls','xlsx','ppt','pptx','gif','jpg','jpeg','bmp','png'];	//允许文件类型
    private $isOverwrite = false; //是否允许覆盖同名文件
    private $isRename    = true; //是否重命名(随机文件名),还是直接使用上传文件的名称
    private $maxSize     = 512; //允许单个文件最大上传尺寸,单位KB

    public $fileInfo     = null; //上传文件信息
    public $params       = null; //单次设置的上传参数


    /**
     * 初始化
     * @param array $defaultParams 默认参数
     */
    public function __construct($defaultParams = []) {
        if(!empty($defaultParams)) {
            if(isset($defaultParams['savePath']))       $this->savePath     = self::formatDir($defaultParams['savePath']);
            if(isset($defaultParams['allowType']))      $this->allowType    = $defaultParams['allowType'];
            if(isset($defaultParams['isOverwrite']))    $this->isOverwrite  = (bool)$defaultParams['isOverwrite'];
            if(isset($defaultParams['isRename']))       $this->isRename     = (bool)$defaultParams['isRename'];
            if(isset($defaultParams['maxSize']))        $this->maxSize      = (int)$defaultParams['maxSize'];
        }

        $this->webDir = '/home/www/';
    }


    /**
     * 返回错误信息
     * @param string $errorCode 错误代码
     * @return mixed
     */
    private function errorInfo($errorCode) {
        return $this->errorInfo[$errorCode];
    }


    /**
     * 上传
     * @param string $inputName 文本域名
     * @param string $newName 新文件名
     * @param array $params 上传参数
     */
    public function upload($inputName, $newName='', $params = array()) {
        $this->parseParams($params);
        $this->fileInfo = array('inputName'=>$inputName, 'newName'=>$newName); //newName包含扩展名

        $check = $this->checkAllStep();
        if($check){
            $res = $this->save();
            if($res) $this->_checkContent();
        }

        return $this->getResult();
    }


    /**
     * 解析(本次)上传参数
     * @param array $params
     */
    private function parseParams($params) {
        $this->params = array(
            'savePath'      => (isset($params['savePath'])) ? self::formatDir($params['savePath']) : $this->savePath,
            'allowType'     => (isset($params['allowType'])) ? $params['allowType'] : $this->allowType,
            'isOverwrite'   => (isset($params['isOverwrite'])) ? (bool)$params['isOverwrite'] : $this->isOverwrite,
            'isRename'      => (isset($params['isRename'])) ? (bool)$params['isRename'] : $this->isRename,
            'maxSize'       => (isset($params['maxSize'])) ? (int)$params['maxSize'] : $this->maxSize,
        );
    }


    /**
     * 设置(本次)上传参数
     * @param $params
     */
    public function setParams($params) {
        $this->parseParams($params);
    }


    /**
     * 检查所有步骤
     */
    private function checkAllStep() {
        $res = $this->_checkSavePath() && $this->_checkTmpDir() && $this->_matchUploadFile()
            && $this->_checkUploadError() && $this->_checkFileSize() && $this->_checkFileExtention();

        return (bool)$res;
    }


    /**
     * 检查保存目录
     * @return bool
     */
    private function _checkSavePath() {
        if(empty($this->params['savePath'])) { //未设置保存目录
            $this->errorCode = -5;
            return false;
        }

        if(!is_dir($this->params['savePath'])) { //目录不存在
            $res = @mkdir($this->params['savePath'], 0755, true);
            if(!$res) $this->errorCode = -6;
            return $res;
        }elseif(!is_writable($this->params['savePath'])) { //目录不可写
            $res = @chmod($this->params['savePath'], 0777);
            if(!$res) $this->errorCode = -7;
            return $res;
        }

        return true;
    }


    /**
     * 检查临时目录
     * @return bool
     */
    private function _checkTmpDir() {
        $tmpDir = self::getTmpDir();
        if(empty($tmpDir) || !is_dir($tmpDir)) {
            $this->errorCode = 6;
            return false;
        }

        return true;
    }


    /**
     * 匹配上传文件
     * @return bool
     */
    private function _matchUploadFile() {
        if($this->_checkFileInput($this->fileInfo['inputName'])) {
            $file                          = $_FILES[$this->fileInfo['inputName']];
            $filename                      = self::autoCharset($file['name']);
            $this->fileInfo['fileName']    = $filename;
            $this->fileInfo['saveName']    = empty($this->fileInfo['newName']) ? self::escapeStr($filename) : $this->fileInfo['newName'];
            $this->fileInfo['fileSize']    = $file['size'];
            $this->fileInfo['fileType']    = self::getExtention($filename);
            $this->errorCode               = $file['error'];

            return true;
        }
        return false;
    }


    /**
     * @return bool
     */
    private function _checkUploadError() {
        $uploadErr = range(1,7);
        $res       = !in_array($this->errorCode, $uploadErr);
        return $res;
    }


    /**
     * 检查上传文件大小
     * @return bool
     */
    private function _checkFileSize(){
        $res = ($this->params['maxSize'] * 1024 >= ($this->fileInfo['fileSize']));
        if(!$res){
            $this->errorCode = -3;
            $this->errorInfo['-3'] .=  $this->params['maxSize'] . 'KB';
        }

        return $res;
    }


    /**
     * 检查上传文件类型
     * @return bool
     */
    private function _checkFileExtention(){
        $res = in_array($this->fileInfo['fileType'], $this->params['allowType']);
        $newName = strtolower($this->fileInfo['saveName']);
        if(!$res){
            $this->errorCode = -4;
            $this->errorInfo['-4'] .=  implode('|', $this->params['allowType']);
        }elseif(strpos($newName, '.php.') !== false|| substr($newName, -4)=='.php') {
            $this->errorCode = -4;
            $this->errorInfo['-4'] .=  '|php';
        }
        return $res;
    }


    /**
     * 检查文件内容
     * @return bool
     */
    private function _checkContent() {
        //检查是否真的图片
        if(in_array($this->fileInfo['fileType'], array('gif','jpg','jpeg','png','bmp'))) {
            $source = $this->params['savePath'] . $this->fileInfo['saveName'];
            if(!$imgSize = $this->getImgSize($source, $this->fileInfo['fileType'])) {
                $this->errorCode = -11;
                return false;
            }
        }

        //其他
        //TODO

        return true;
    }


    /**
     * 检查是否有相应的文件域
     * @param string $inputName 上传文件在FORM表单中的名字(文件域)
     * @return bool
     */
    private function _checkFileInput($inputName){
        $res = isset($_FILES[$inputName]);
        if(!$res) $this->errorCode = -2;
        return $res;
    }


    /**
     * escape编码
     * @param string $string
     * @return mixed
     */
    public static function escapeStr($string) {
        $string = str_replace(["\0","%00","\r"], '', $string);
        $string = preg_replace(['/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]/','/&(?!(#[0-9]+|[a-z]+);)/is'], ['', '&amp;'], $string);
        $string = str_replace(["%3C",'<'], '&lt;', $string);
        $string = str_replace(["%3E",'>'], '&gt;', $string);
        $string = str_replace(['"',"'","\t",'  '], ['&quot;','&#39;','    ','&nbsp;&nbsp;'], $string);
        return $string;
    }


    /**
     * 格式化目录[路径后面加/]
     * @param string $dir
     * @return string
     */
    public static function formatDir($dir) {
        $dir = str_replace(["'",'#','=','`','$','%','&',';'], '', $dir);
        return rtrim(preg_replace('/(\/){2,}|(\\\){1,}/', '/', $dir), '/');
    }


    /**
     * 保存
     * @return bool
     */
    private function save() {
        //是否存在上传的临时文件
        if($this->fileInfo['tmp_name']=='' || !file_exists($this->fileInfo['tmp_name'])) {
            $this->errorCode = -8;
            return false;
        }

        //重命名文件
        if($this->params['isRename'] || empty($this->fileInfo['saveName'])) {
            $this->fileInfo['saveName'] = self::createRandName($this->fileInfo['fileTmpName']) . '.' . $this->fileInfo['fileType'];
        }

        //新文件路径
        $newFilePath = $this->params['savePath'] . $this->fileInfo['saveName'];

        //是否覆盖同名文件
        if(file_exists($newFilePath) && !$this->params['isOverwrite']){
            $this->errorCode = -9;
            return false;
        }

        $res = self::saveFile($this->fileInfo['fileTmpName'], $newFilePath);
        if(!$res) {
            $this->errorCode = -10;
        }else{
            $this->errorCode = 99;
        }

        return $res;
    }


    /**
     * 保存文件
     * @param string $tmpFilePath 临时文件路径
     * @param string $newFilePath 新文件路径
     * @return bool
     */
    public static function saveFile($tmpFilePath, $newFilePath) {
        if(function_exists("move_uploaded_file") && @move_uploaded_file($tmpFilePath, $newFilePath)) {
            @chmod($newFilePath, 0755);
            return true;
        }elseif(@copy($tmpFilePath, $newFilePath)) {
            @chmod($newFilePath, 0755);
            return true;
        }

        return false;
    }


    /**
     * 获取图片的大小
     * @param string $srcFile 图片地址
     * @param null $srcExt 图片类型
     * @return array|bool
     */
    private function getImgSize($srcFile, $srcExt = null) {
        empty($srcExt) && $srcExt = strtolower(substr(strrchr($srcFile, '.'), 1));
        $srcdata = [];
        if (function_exists('read_exif_data') && in_array($srcExt, [
                'jpg',
                'jpeg',
                'jpe',
                'jfif'
            ])) {
            $datatemp = @read_exif_data($srcFile);
            $srcdata['width'] = $datatemp['COMPUTED']['Width'];
            $srcdata['height'] = $datatemp['COMPUTED']['Height'];
            $srcdata['type'] = 2;
            unset($datatemp);
        }
        !$srcdata['width'] && list($srcdata['width'], $srcdata['height'], $srcdata['type']) = @getimagesize($srcFile);
        if (!$srcdata['type'] || ($srcdata['type'] == 1 && in_array($srcExt, [
                    'jpg',
                    'jpeg',
                    'jpe',
                    'jfif'
                ]))) {
            return false;
        }
        return $srcdata;
    }

    /**
     * 获取上传结果
     * @return array
     */
    public function getResult() {
        $this->result = [
            'status' => (bool)($this->errorCode==99), //上传结果
            'info' => $this->errorInfo[$this->errorCode], //提示信息
            'absolutePath' => $this->params['savePath'] . $this->fileInfo['saveName'], //绝对路径
            'relativePath' => str_replace($this->webDir, '', $this->params['savePath']) . $this->fileInfo['saveName'], //相对WEB目录路径
            'name' => $this->fileInfo['saveName'], //保存的文件名
            'size' => $this->fileInfo['fileSize'], //文件大小,单位bit
        ];

        return $this->result;
    }


    /**
     * 获取临时目录
     * @return string
     */
    public static function getTmpDir() {
        return ini_get('upload_tmp_dir') ? ini_get('upload_tmp_dir') : sys_get_temp_dir();
    }


    /**
     * 获取文件扩展名
     * @param string $fileName 文件名
     * @return string
     */
    public static function getExtention($fileName) {
        $arr = pathinfo($fileName);
        return strtolower($arr['extension']);
    }


    /**
     * 自动转换字符为UTF8
     * @param $string
     * @return string
     */
    public static function autoCharset($string) {
        if(function_exists('mb_detect_encoding')){
            $encode = mb_detect_encoding($string, ['ASCII','GB2312','GBK','BIG5','UTF-8']);
            if($encode != 'UTF-8'){
                $string = mb_convert_encoding($string,'UTF-8',$encode);
            }
        }else{
            $string = @iconv('GB2312','UTF-8//IGNORE',$string);
        }

        return $string;
    }


    /**
     * 生成随机文件名(不包含扩展名)
     * @param string $str
     * @return string
     */
    public static function createRandName($str='') {
        $date = date('ymdHis');
        $uniq = md5(uniqid(rand(),true));
        $rand = substr(md5($str), 8, 4);

        return $date . $uniq . $rand;
    }


}