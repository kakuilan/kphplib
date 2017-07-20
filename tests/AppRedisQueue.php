<?php
/**
 * Created by PhpStorm.
 * User: kakuilan@163.com
 * Date: 2017/7/20
 * Time: 15:04
 * Desc: APP的redis队列类
 */

namespace Lkk\Tests;

use \Lkk\LkkRedisQueueService;

class AppRedisQueue extends LkkRedisQueueService {

    const APP_WORKFLOW_QUEUE_NAME = 'app_workflow'; //APP工作流队列名称
    const APP_NOTIFY_QUEUE_NAME = 'app_notify'; //APP应用通知队列名称
    const APP_NOTIFY_RESEND_TIMES = 50; //APP应用通知重发次数


    /**
     * 快速添加单个消息到工作流队列
     * @param array $item 消息:例如['type'=>'register', 'data'=>[]]
     * @param array $conf 配置
     * @return array
     */
    public static function quickAddItem2WorkflowMq($item=[], $conf=[]) {
        static $queues = [];
        $key = md5(self::APP_WORKFLOW_QUEUE_NAME. json_encode($conf));

        if(!isset($queues[$key]) || empty($queues[$key])) {
            $queue = new AppRedisQueue($conf);
            $queue->newQueue(self::APP_WORKFLOW_QUEUE_NAME);
            $queues[$key] = $queue;
        }else{
            $queue = $queues[$key];
        }

        $res = $queue->push($item);
        $data = [
            'result' => $res,
            'error' => $queue->error,
        ];

        return $data;


    }


    /**
     * 快速添加单个消息到APP通知队列
     * @param array $item 消息:例如['type'=>'msg', 'data'=>[]],type类型有msg站内信,sms短信,mail邮件,getui个推,other其他
     * @param array $conf 配置
     * @return array
     */
    public static function quickAddItem2AppNotifyMq($item=[], $conf=[]) {
        static $queues = [];
        $key = md5(self::APP_NOTIFY_QUEUE_NAME. json_encode($conf));

        if(!isset($queues[$key]) || empty($queues[$key])) {
            $queue = new AppRedisQueue($conf);
            $queue->newQueue(self::APP_NOTIFY_QUEUE_NAME);
            $queues[$key] = $queue;
        }else{
            $queue = $queues[$key];
        }

        $res = $queue->push($item);
        $data = [
            'result' => $res,
            'error' => $queue->error,
        ];

        return $data;
    }



    /**
     * 快速添加多个消息到APP用户通知队列
     * @param array $items 消息数组
     * @param array $conf 配置
     * @return array
     */
    public static function quickAddMultItem2AppNotifyMq($items=[], $conf=[]) {
        static $queues = [];
        $key = md5(json_encode($conf));

        if(!isset($queues[$key]) || empty($queues[$key])) {
            $queue = new AppRedisQueue($conf);
            $queue->newQueue(self::APP_NOTIFY_QUEUE_NAME);
            $queues[$key] = $queue;
        }else{
            $queue = $queues[$key];
        }

        $res = $queue->pushMulti($items);
        $data = [
            'result' => $res,
            'error' => $queue->error,
        ];

        return $data;
    }




}