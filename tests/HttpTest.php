<?php
/**
 * Created by PhpStorm.
 * User: kakuilan@163.com
 * Date: 2017/7/17
 * Time: 10:40
 * Desc: http类测试
 */


namespace Lkk\Tests;

use PHPUnit\Framework\TestCase;
use Lkk\LkkHttp;
use Lkk\Helpers\DirectoryHelper;

class HttpTest extends TestCase {


    public function testHttp() {
        $savePath = TESTDIR . 'tmp' . DS;
        DirectoryHelper::mkdirDeep($savePath);

        $http = new LkkHttp();

        $url1 = 'https://www.baidu.com/';
        $url2 = 'http://www.163.com/';
        $url3 = 'https://id.ifeng.com/api/sitelogin/';
        $url4 = 'http://www.ifeng.com/';
        $url5 = 'http://www.sina.com.cn/';

        //状态码
        $code = $http->getHttpStatusCode($url1);
        $this->assertEquals($code, '200');

        //get请求
        $res2 = $http->get($url2);
        $this->assertNotEmpty($res2);

        //post请求
        $getData = ['_time'=>time()];
        $postData = [
            'u' => 'heheda',
            'k' => '123456',
            'auth' => '7clm',
        ];
        $cookData = ['uname'=>'test'];
        $res3 = $http->get($url3, $getData, $postData, $cookData);
        $this->assertNotEmpty($res3);

        //异步请求
        $res4 = $http->request($url4);
        $this->assertTrue($res4);

        //保存
        $file = md5(time() . mt_rand(1000, 9999));
        $res5 = $http->save($url5, $savePath, $file);
        $this->assertTrue($res4);

    }


}