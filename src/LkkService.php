<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/7/14
 * Time: 16:20
 * Desc: -lkk 服务类
 */

namespace Lkk;

class LkkService extends LkkObject {

    protected $errno, $error;


    public function __construct($vars=[]) {
        parent::__construct($vars);
    }


    public function __destruct() {

    }


    /**
     * 获取错误代码
     * @return mixed
     */
    public function errno() {
        return $this->errno;
    }


    /**
     * 获取错误信息
     * @return mixed
     */
    public function error() {
        return $this->error;
    }


    /**
     * 设置服务错误
     * @param string $error 错误信息
     * @param string $errno 错误代码
     */
    public function setError($error='', $errno='') {
        $this->error = $error;
        $this->errno = $errno;
    }


    /**
     * 获取服务错误
     * @return array
     */
    public function getError() {
        return [
            'errno' => $this->errno,
            'error' => $this->error,
        ];
    }

}