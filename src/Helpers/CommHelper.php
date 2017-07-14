<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/4/2
 * Time: 14:53
 * Desc: -通用助手类
 */


namespace Lkk\Helpers;

use Phalcon\Logger\Formatter\Line as ExtFormatter;
use Lkk\ExtLogger;
use Monolog\Logger as Monologger;
use Monolog\Handler\StreamHandler as MonoStreamHandler;


 class CommHelper {


     /**
      * 获取扩展日志对象
      * @param string $logname 日志名
      *
      * @return mixed
      */
     public static function getExtLogger($logname='') {
         static $extLoggers;
         $logname = trim($logname);
         if($logname=='') $logname='commm';
         if(!isset($extLoggers[$logname])) {
             $file = LOGDIR . "{$logname}.log";
             $logger = new ExtLogger($file);

             // 修改日志格式
             $formatter = new ExtFormatter("[%date% %type%] %message%");
             $formatter->setDateFormat('Y-m-d H:i:s');
             $logger->setFormatter($formatter);

             $extLoggers[$logname] = $logger;
         }

         return $extLoggers[$logname];
     }


     /**
      * 获取Monolog日志对象
      * @param string $logname
      *
      * @return mixed
      */
     public static function getMonLogger($logname='') {
         static $monLoggers;
         $logname = trim($logname);
         if($logname=='') $logname='commm';
         if(!isset($monLoggers[$logname])) {
             $file = LOGDIR . "{$logname}.log";
             $logger = new Monologger($logname);
             $logger->pushHandler(new MonoStreamHandler($file, Monologger::INFO));
             $monLoggers[$logname] = $logger;
         }

         return $monLoggers[$logname];
     }




 }