<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/7/14
 * Time: 16:15
 * Desc:
 */


define('DS', str_replace('\\', '/', DIRECTORY_SEPARATOR));
define('PS', PATH_SEPARATOR);
define('TESTDIR', str_replace('\\', '/', __DIR__ . DS ) ); //根目录

$loader = require __DIR__ .'/../vendor/autoload.php';