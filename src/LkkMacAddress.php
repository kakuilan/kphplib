<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/7/14
 * Time: 16:27
 * Desc: -lkk 服务器物理地址类
 */


namespace Lkk;

class LkkMacAddress {

    public static $addr_arr,$mac_addr;
    private static $valid_mac = "([0-9A-F]{2}[:-]){5}([0-9A-F]{2})";


    /**
     * 获取服务器网卡地址
     * @return string
     */
    public static function getMacAddress() {
        $os_type = strtolower(PHP_OS);

        switch ($os_type) {
            case "linux":
                self::_parseLinux();
                break;
            case "solaris":
                break;
            case "unix":
                break;
            case "aix":
                break;
            default:
                self::_parseWindow();
                break;
        }


        if(self::$mac_addr) {
            return self::$mac_addr;
        }else{
            foreach (self::$addr_arr as $item) {
                if(preg_match("/" . self::$valid_mac . "/i", $item, $match)) {
                    self::$mac_addr = strtoupper($match[0]);
                    break;
                }
            }
        }

        return self::$mac_addr;
    }


    /**
     * 解析linux系统
     * @return mixed
     */
    private static function _parseLinux() {
        @exec("ifconfig -a", self::$addr_arr);
        return self::$addr_arr;
    }


    /**
     * 解析window系统
     * @return mixed
     */
    private static function _parseWindow() {
        @exec("ipconfig /all", self::$addr_arr);

        if(empty(self::$addr_arr)){
            $ipconfig = $_SERVER['windir'] . "\system32\ipconfig.exe";
            if(is_file($ipconfig)) {
                @exec($ipconfig . " /all", self::$addr_arr);
            }else{
                @exec($_SERVER['windir'] . "\system\ipconfig.exe /all", self::$addr_arr);
            }

        }

        return self::$addr_arr;
    }

}