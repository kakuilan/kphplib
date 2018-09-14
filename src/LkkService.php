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

    public $errno, $error;
    protected static $instance; //供静态绑定
    protected static $_instance; //供最终子类绑定


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
     * 实例化并返回[父类]
     * @param array $vars
     * @return mixed
     */
    public static function instance(array $vars = []) {
        if(is_null(self::$instance) || !is_object(self::$instance) || !(self::$instance instanceof self)) {
            self::$instance = new self($vars);
        }

        return self::$instance;
    }


    /**
     * 实例化并返回[静态绑定,供(当前)子类调用]
     * @param array $vars
     *
     * @return mixed
     */
    public static function getInstance(array $vars = []) {
        if(is_null(static::$_instance) || !is_object(static::$_instance) || !(static::$_instance instanceof static)) {
            //静态延迟绑定
            static::$_instance = new static($vars);
        }

        return static::$_instance;
    }


    /**
     * 销毁实例化对象
     */
    public static function destroy() {
        self::$instance = null;
        static::$_instance = null;
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