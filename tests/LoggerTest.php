<?php
/**
 * Created by PhpStorm.
 * User: kakuilan@163.com
 * Date: 2017/7/17
 * Time: 11:14
 * Desc: 日志类测试
 */


namespace Lkk\Tests;

use PHPUnit\Framework\TestCase;
use Lkk\LkkLogger;
use Lkk\Helpers\DirectoryHelper;

class LoggerTest extends TestCase {

    public function testLog() {
        $savePath = TESTDIR . 'tmp' . DS;
        $logName = 'testlog_' .md5(time() . mt_rand(1111, 9999));
        $logger = new LkkLogger($logName, $savePath);
        $logFile = $logger->getLogFilePath();

        $logger->emergency('logger test', ['server'=>$_SERVER]);
        $logger->alert('logger test', ['server'=>$_SERVER]);
        $logger->critical('logger test', ['server'=>$_SERVER]);
        $logger->error('logger test', ['server'=>$_SERVER]);
        $logger->warning('logger test', ['server'=>$_SERVER]);
        $logger->notice('logger test', ['server'=>$_SERVER]);
        $logger->info('logger test', ['server'=>$_SERVER]);
        $logger->debug('logger test', ['server'=>$_SERVER]);

        $exis = file_exists($logFile);
        $this->assertEquals($exis, true);

    }

}