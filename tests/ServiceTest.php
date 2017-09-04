<?php
/**
 * Created by PhpStorm.
 * User: kakuilan@163.com
 * Date: 2017/7/14
 * Time: 16:36
 * Desc: 服务类测试
 */


namespace Lkk\Tests;

use PHPUnit\Framework\TestCase;
use Lkk\LkkService;
use Lkk\LkkMacAddress;


class ServiceTest extends TestCase {


    /**
     * 测试新建服务
     */
    public function testNewService() {
        $serv = new LkkService();
        $serv->setError('发生错误', 500);
        $errno = $serv->errno();
        $error = $serv->error();
        $errors = $serv->getError();

        $this->assertEquals($errno, $errors['errno']);
        $this->assertEquals($error, $errors['error']);
    }


    /**
     * 测试获取网卡地址
     */
    public function testGetMacAddr() {
        $mac = LkkMacAddress::getMacAddress();
        $len = strlen($mac);
        $this->assertEquals($len, 17);
    }


    /**
     * 测试服务实例
     */
    public function testServiceInstance() {
        $ins1 = AppRedisQueue::instance();
        $cls1 = get_class($ins1);
        $ins2 = AppRedisQueue::getInstance();
        $cls2 = get_class($ins2);

        $chk1 = $ins1 instanceof LkkService;
        $chk2 = $ins2 instanceof AppRedisQueue;

        $this->assertTrue($chk1);
        $this->assertTrue($chk2);
    }



}