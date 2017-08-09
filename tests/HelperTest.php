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

class HelperTest extends TestCase {

    public function testMurmurhash() {
        $num = mt_rand(0, 9999);
        $res = EncryptHelper::murmurhash3_int($num);
        $len = strlen($res);

        $this->assertTrue(is_numeric($res));
        $this->assertEquals($len, 11);
    }


    public function testNumeric() {
        $str = '111.0';
        $res1 = ValidateHelper::isInteger($str);
        $res2 = ValidateHelper::isFloat($str);
        $this->assertEquals($res1, false);
        $this->assertEquals($res2, true);
    }




}