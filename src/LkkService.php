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
    protected static $instance;


    /**
     * 构造函数
     * LkkService constructor.
     * @param array $vars
     */
    public function __construct($vars=[]) {
        parent::__construct($vars);
    }


    /**
     * 析构函数
     */
    public function __destruct() {

    }


    /**
     * 实例化
     * @param array $vars
     * @return mixed
     */
    public static function instance(array $vars = []) {
        if(is_null(self::$instance)) {
            //静态延迟绑定
            static::$instance = new static($vars);
        }

        return static::$instance;
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