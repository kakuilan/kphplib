<?php
/**
 * Created by PhpStorm.
 * User: kakuilan@163.com
 * Date: 2017/7/20
 * Time: 14:08
 * Desc:
 */

namespace Lkk\Tests;

use PHPUnit\Framework\TestCase;
use Lkk\LkkRedisQueueService;
use Lkk\LkkLogger;
use Lkk\LkkMacAddress;
use Lkk\Helpers\DirectoryHelper;

/**
 * Class RedisQueueTest
 * @package Lkk\Tests
 */
class RedisQueueTest extends TestCase {


    /**
     * 测试实例化
     */
    public function testInstance() {
        $queue = LkkRedisQueueService::getInstance();
        $check = ($queue instanceof LkkRedisQueueService);

        $this->assertTrue($check);
    }


    /**
     * 测试队列添加单个消息
     */
    public function testQueueAddItem() {
        $faker = \Faker\Factory::create();
        $data = [
            'id' => $faker->uuid,
            'name' => $faker->name,
            'time' => $faker->time('Y-m-d H:i:s'),
            'address' => $faker->address
        ];

        $addRes = AppRedisQueue::quickAddItem2AppNotifyMq($data);
        $res = (is_array($addRes) && isset($addRes['result']) && $addRes['result']===true);
        $this->assertTrue($res);
    }


    /**
     * 测试队列添加多个消息
     */
    public function testQueueAddMultiItem() {
        $faker = \Faker\Factory::create();
        $datas = [];

        for ($i=0;$i<=100;$i++) {
            $item = [
                'id' => $faker->uuid,
                'name' => $faker->name,
                'time' => $faker->time('Y-m-d H:i:s'),
                'address' => $faker->address
            ];
            array_push($datas, $item);
        }

        $addRes = AppRedisQueue::quickAddMultItem2AppNotifyMq($datas);
        $res = (is_array($addRes) && isset($addRes['result']) && $addRes['result']===true);
        $this->assertTrue($res);
    }


    /**
     * 测试拉取消息并处理
     */
    public function testPullNotifyMsg() {
        $savePath = TESTDIR . 'tmp' . DS;
        $logName = 'queuetest';
        $logger = new LkkLogger($logName, $savePath);

        $queue = new AppRedisQueue([]);
        $queue->newQueue(AppRedisQueue::APP_NOTIFY_QUEUE_NAME);
        $len = $queue->len();
        $procedNum = 0; //已处理的消息数量
        $succesNum = 0; //成功的数量

        $logger->info('开始拉取消息,队列长度:'. $len);

        while ($procedNum< $len && $item = $queue->pop()) {
            $msg_key = AppRedisQueue::getItemProcessKey($item);
            $logger->info('receive a msg: [msg_key:'.$msg_key.']', $item);

            $handlRes = (bool)mt_rand(0, 1);
            if($handlRes) {
                $succesNum++;
                $queue->confirm($item, $handlRes);
            }
            $procedNum++;
        }

        $failNum = $len - $succesNum;
        $logger->info("已处理完毕,总共:{$len},成功:{$succesNum},失败:{$failNum}");

        $res = (0 == $queue->len());
        $this->assertTrue($res);

        return $failNum;
    }


    /**
     * @depends testPullNotifyMsg
     * 测试失败的消息重新入列
     * @param int $lastFailNum 上次失败的数量
     */
    public function testFailMsgReaddQueue($lastFailNum) {
        $savePath = TESTDIR . 'tmp' . DS;
        $logName = 'queuetest';
        $logger = new LkkLogger($logName, $savePath);

        $readdNum = 0;
        sleep(2);
        $conf = ['transTime'=>1]; //消息过期时间
        $queue = new AppRedisQueue($conf);
        $addr = LkkMacAddress::getMacAddress();
        $readdRes = $queue->loopTransQueue($addr);
        $error = $queue->getError();

        $logger->info("共重新入列:{$readdRes}");

        $res = (is_numeric($readdRes) && $readdRes==$lastFailNum);
        $this->assertTrue($res);
    }


    /**
     * 测试批量确认
     */
    public function testConfirmMult() {
        $maxNum = 10000;
        $msgArr = [];
        $faker = \Faker\Factory::create();

        $queueName = 'gps_test';
        $queue = new AppRedisQueue();
        $queue->newQueue($queueName);

        $startTime = strtotime('2016-12-01');
        $endTime = strtotime('2017-12-01');
        $entitys = ['firstTruck','secondTruck','thirdTruck'];
        $newNum = $pullNum = $sucNum = $faiNum = $confirmNum = 0;
        for ($i=0;$i<$maxNum;$i++) {
            $entity_name = $entitys[array_rand($entitys)];
            $lat = $faker->randomFloat(9, 10, 60);
            $lng = $faker->randomFloat(9, 80, 150);
            $speed = $faker->randomFloat(2, 30, 120);
            $height = $faker->numberBetween(-50, 1500);
            $direction = $faker->numberBetween(0, 359);
            $loc_time = $faker->numberBetween($startTime, $endTime);

            $gpsItem = [
                'entity_name' => $entity_name,
                'lat' => $lat,
                'lng' => $lng,
                'speed' => $speed,
                'height' => $height,
                'direction' => $direction,
                'loc_time' => $loc_time,
            ];
            $res = $queue->push($gpsItem);
            if($res) $newNum++;
        }

        //取出
        $keys = [];
        while ($item = $queue->pop()) {
            $pullNum++;
            $item = (array)$item;
            $key = $queue->getTranItemKey($item);
            array_push($keys, $key);
            array_push($msgArr, $item);
        }

        //批量确认
        $confirmNum = $queue->confirmMult($keys, true, 50);
        $this->assertEquals($newNum, $maxNum);
        $this->assertEquals($pullNum, $maxNum);
        $this->assertEquals($confirmNum, $maxNum);
    }





}