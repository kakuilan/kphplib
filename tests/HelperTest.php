<?php
/**
 * Created by PhpStorm.
 * User: kakuilan@163.com
 * Date: 2017/8/7
 * Time: 19:01
 * Desc: 助手类测试
 */

namespace Lkk\Tests;

use PHPUnit\Framework\TestCase;
use Lkk\LkkHttp;
use Lkk\Helpers\DirectoryHelper;
use Lkk\Helpers\EncryptHelper;
use Lkk\Helpers\ValidateHelper;
use Lkk\Helpers\ArrayHelper;
use Lkk\Helpers\CommonHelper;

class HelperTest extends TestCase {


    /**
     * murmurhash3算法
     */
    public function testMurmurhash() {
        $num = mt_rand(0, 9999);
        $res = EncryptHelper::murmurhash3_int($num, 0, true);
        $len = strlen($res);

        $this->assertTrue(is_numeric($res));
        $this->assertLessThanOrEqual(11, $len);
    }


    /**
     * 数值类型检查
     */
    public function testNumeric() {
        $str = '111.0';
        $res1 = ValidateHelper::isInteger($str);
        $res2 = ValidateHelper::isFloat($str);
        $this->assertEquals($res1, false);
        $this->assertEquals($res2, true);
    }


    /**
     * 数组元素搜索
     */
    public function testArraySearch() {
        $now = time();
        $arr = [
            [
                'id' => 0,
                'name' => 'helloWorld',
                'time' => $now,
                'month' => '08',
                'country' => 'China',
            ]
        ];
        $maxNum = 10000;
        $faker = \Faker\Factory::create();

        for ($i=1;$i<=$maxNum;$i++) {
            $item = [
                'id' => $i,
                'name' => $faker->name,
                'time' => $faker->unixTime,
                'month' => $faker->month,
                'country' => $faker->country
            ];
            array_push($arr, $item);
        }
        shuffle($arr);

        $res1 = ArrayHelper::arraySearchItem($arr, ['id'=>0]);
        $res2 = ArrayHelper::arraySearchMutilItem($arr, ['name'=>'helloWorld','time'=>$now,'month'=>'08']);
        $res3 = ArrayHelper::arraySearchItem($arr, ['id'=>99], true);
        $len = count($arr);

        $this->assertNotEmpty($res1);
        $this->assertNotEmpty($res2);
        $this->assertNotEmpty($res3);
        $this->assertEquals($len, $maxNum);
    }


    /**
     * 测试ipv4转为long型
     */
    public function testIp2Long() {
        //$ip = '192.168.101.100';
        $faker = \Faker\Factory::create();
        $ip = $faker->ipv4;

        $long1 = ip2long($ip);
        $long2 = CommonHelper::ip2UnsignedInt($ip);
        $ip2 = long2ip($long2);

        $this->assertEquals($ip, $ip2);
    }



}