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


class PromiseTest extends TestCase {

    private static function randFilea() {
        $now = time();
        $file = __DIR__ ."/log_{$now}_a";
        touch($file);
        yield;
    }

    private static function randFileb() {
        $now = time();
        $file = __DIR__ ."/log_{$now}_b";
        touch($file);
        yield;
    }

    private static function randFilec() {
        $now = time();
        $file = __DIR__ ."/log_{$now}_c";
        touch($file);
        yield;
    }

    public function testPromise() {
        Promise::co(function() {
            yield self::randFilea();
            yield self::randFileb();
            yield self::randFilec();
        });
    }




}