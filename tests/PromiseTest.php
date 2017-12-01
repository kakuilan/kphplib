<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/11/30
 * Time: 18:04
 * Desc:
 */

namespace Lkk\Tests;

use PHPUnit\Framework\TestCase;

use Lkk\Concurrent\Promise;
use Lkk\Helpers\CommonHelper;

class PromiseTest extends TestCase {

    private static function randFilea() {
        $total = 10;
        for($i=1;$i<=$total;$i++) {
            $now = CommonHelper::getMillisecond();
            echo "This is task a iteration $i.\n";
            $file = __DIR__ ."/log_{$now}_a_{$i}";
            touch($file);
            yield;
        }
    }

    private static function randFileb() {
        $total = 10;
        for($i=1;$i<=$total;$i++) {
            $now = CommonHelper::getMillisecond();
            echo "This is task b iteration $i.\n";
            $file = __DIR__ ."/log_{$now}_b_{$i}";
            touch($file);
            yield;
        }
    }

    private static function randFilec() {
        $total = 10;
        for($i=1;$i<=$total;$i++) {
            $now = CommonHelper::getMillisecond();
            echo "This is task c iteration $i.\n";
            $file = __DIR__ ."/log_{$now}_c_{$i}";
            touch($file);
            yield;
        }
    }

    
    public function testPromise() {
        Promise::co(function() {
            yield self::randFilea();
            yield self::randFileb();
            yield self::randFilec();
        });

    }




}