<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/7/14
 * Time: 16:25
 * Desc: -lkk 日志类
 */

namespace Lkk;

class LkkLogger {

    private static $limitSize   = 104857600;//日志大小限制100M
    private static $retryNum    = 10;//写失败后尝试次数
    private $logFilePath        = null;//日志文件路径
    private $fileHandle         = null;//日志文件句柄
    private $dateFormat         = 'Y-m-d H:i:s';
    private $prefix             = null;//日志内容前缀
    public	$logDir             = '/var/log/';//日志目录
    public  static $DS          = null; //目录符号

    //日志级别
    private $logLevels = array(
        'EMERGENCY' => 0,
        'ALERT'     => 1,
        'CRITICAL'  => 2,
        'ERROR'     => 3,
        'WARNING'   => 4,
        'NOTICE'    => 5,
        'INFO'      => 6,
        'DEBUG'     => 7,
    );
    private $logLevelThreshold = 'INFO';


    /**
     * LkkLogger constructor.
     *
     * @param string $logFileName 日志文件名
     * @param string $logDirectory 日志目录
     * @param string $logLevelThreshold 日志级别
     */
    public function __construct($logFileName, $logDirectory='', $logLevelThreshold='INFO'){
        self::$DS = str_replace('\\', '/', DIRECTORY_SEPARATOR);

        $this->setLogFile($logFileName, $logDirectory);

        !file_exists($this->logFilePath) && touch($this->logFilePath);
        if(!is_writable($this->logFilePath)){
            return false;
        }

        $this->logLevelThreshold = $logLevelThreshold;
        return true;
    }


    /**
     * 析构函数
     */
    public function __destruct(){
        $this->flush();
    }


    /**
     * 将日志写入文件
     */
    public function flush(){
        if($this->fileHandle){
            @fclose($this->fileHandle);
        }

        if(filesize($this->logFilePath) >= self::$limitSize){
            $backupFile = $this->logFilePath . date('.YmdHis.') .'bak';
            @rename($this->logFilePath, $backupFile);
        }
    }


    /**
     * 设置日志路径
     * @param string $logFileName 日志文件
     * @param string $logDirectory 日志目录
     */
    public function setLogFile($logFileName='', $logDirectory=''){
        if(empty($logDirectory)) $logDirectory = $this->logDir;
        $logDirectory = $this->checkDirectory($logDirectory);

        if(empty($logFileName)) {
            $logFileName = 'undefined_' .date('Ymd').'.log';
        }else{
            $pathArr = pathinfo($logFileName);
            if(!$pathArr['dirname']=='.') { //文件名包含目录
                if(is_dir($pathArr['dirname'])) { //若该目录存在
                    $logDirectory = $this->checkDirectory($pathArr['dirname']);
                }else{
                    $logDirectory = $logDirectory . self::$DS . $this->checkDirectory($pathArr['dirname']);
                }
            }

            if(isset($pathArr['extension'])) { //文件名有后缀
                $logFileName = $pathArr['basename'];
            }else{
                $logFileName = $pathArr['basename'].'.log';
            }
        }

        $this->logDir = $logDirectory;
        $this->logFilePath = $this->logDir . self::$DS . $logFileName;
    }


    /**
     * 获取日志文件路径
     * @return null|string
     */
    public function getLogFilePath() {
        return $this->logFilePath;
    }


    /**
     * 设置日期格式
     * @param string $dateFormat
     */
    public function setDateFormat($dateFormat='Y-m-d H:i:s'){
        $this->dateFormat = $dateFormat;
    }


    /**
     * 设置日志级别
     * @param string $logLevelThreshold
     */
    public function setLogLevelThreshold($logLevelThreshold='INFO'){
        $this->logLevelThreshold = $logLevelThreshold;
    }


    /**
     * 记录日志内容
     * @param string $level 级别
     * @param string $message 消息
     * @param array $context 内容
     * @return bool
     */
    public function log($level='',$message='', $context=[]){
        if(!$this->fileHandle) $this->fileHandle = fopen($this->logFilePath, 'a');
        if(!$this->fileHandle) return false;

        $message = $this->formatMessage($level, $message, $context);
        $this->write($message);
        return true;
    }

    /**
     * 检查目录
     * @param $dir
     * @return string
     */
    private function checkDirectory($dir) {
        $dir = rtrim($dir, '\\/');
        if (!file_exists($dir)) {
            if(!mkdir($dir, 0755, true)) {
                return '';
            }
        }

        return $dir;
    }


    /**
     * 写文件
     * @param $message
     * @return int
     */
    private function write($message){
        $i = 0;
        while($i <= self::$retryNum){
            if(flock($this->fileHandle,LOCK_EX)){
                $res = fwrite($this->fileHandle, $message);
                flock($this->fileHandle,LOCK_UN);
                return $res;
                break;
            }else{
                $i++;
                usleep(1000);
            }
        }
        return $i;
    }


    /**
     * 格式化日志内容
     * @param $level
     * @param $message
     * @param $context
     * @return string
     */
    private function formatMessage($level, $message, $context){
        $level = strtoupper($level);
        if (! empty($context)) {
            $message .= PHP_EOL .$this->indent($this->contextToString($context));
        }
        if($this->prefix ===null){//默认
            return "[{$this->getTimestamp()} {$level}] {$message}".PHP_EOL;
        }else{
            return "{$this->prefix} {$message}".PHP_EOL;
        }
    }


    /**
     * 设置日志行前缀内容
     * @param $string
     */
    public function setPrefix($string){
        $this->prefix = $string;
    }


    /**
     * 获取时间戳
     * @return string
     */
    private function getTimestamp(){
        $originalTime = microtime(true);
        $micro = sprintf("%06d", ($originalTime - floor($originalTime)) * 1000000);
        $date = new \DateTime(date('Y-m-d H:i:s.'.$micro, $originalTime));

        return $date->format($this->dateFormat);
    }


    /**
     * 将日志内容转换为字符串
     * @param $context
     * @return mixed
     */
    private function contextToString($context){
        $export = '';
        if(!is_array($context) && !is_object($context)) $context = (array)$context;
        foreach ($context as $key => $value) {
            $export .= "{$key}: ";
            $export .= preg_replace([
                '/=>\s+([a-zA-Z])/im',
                '/array\(\s+\)/im',
                '/^  |\G  /m',
            ], [
                '=> $1',
                'array()',
                '    ',
            ], str_replace('array (', 'array(', var_export($value, true)));
            $export .= PHP_EOL;
        }
        return str_replace(['\\\\', '\\\''], ['\\', '\''], rtrim($export));
    }


    /**
     * 缩进
     * @param $string
     * @param string $indent
     * @return string
     */
    private function indent($string, $indent = '    '){
        return $indent.str_replace("\n", "\n".$indent, $string);
    }


    /**
     * 紧急
     * @param $message
     * @param array $context
     */
    public function emergency($message, $context =[]){
        $this->log('EMERGENCY', $message, $context);
    }


    /**
     * 警示
     * @param $message
     * @param array $context
     */
    public function alert($message, $context =[]){
        $this->log('ALERT', $message, $context);
    }


    /**
     * 严重
     * @param $message
     * @param array $context
     */
    public function critical($message, $context =[]){
        $this->log('CRITICAL', $message, $context);
    }


    /**
     * 错误
     * @param $message
     * @param array $context
     */
    public function error($message, $context =[]){
        $this->log('ERROR', $message, $context);
    }


    /**
     * 警告
     * @param $message
     * @param array $context
     */
    public function warning($message, $context =[]){
        $this->log('WARNING', $message, $context);
    }


    /**
     * 注意
     * @param $message
     * @param array $context
     */
    public function notice($message, $context =[]){
        $this->log('NOTICE', $message, $context);
    }


    /**
     * 消息
     * @param $message
     * @param array $context
     */
    public function info($message, $context =[]){
        $this->log('INFO', $message, $context);
    }


    /**
     * 调试
     * @param $message
     * @param array $context
     */
    public function debug($message, $context =[]){
        $this->log('DEBUG', $message, $context);
    }


}