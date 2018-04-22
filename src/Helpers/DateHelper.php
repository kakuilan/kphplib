<?php
/**
 * Copyright (c) 2016 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2016/12/25
 * Time: 16:58
 * Desc: -日期助手类
 */


namespace Lkk\Helpers;

class DateHelper {


    /**
     * 格式化日期时间
     * @param int $datetemp 时间戳
     * @param string $formate
     * @return mixed
     */
    public static function formatDatetime($datetemp=0, $formate = 'Y-n-j H:i') {
        $sec = time() - (is_numeric($datetemp) ? $datetemp : strtotime($datetemp));
        $hour = floor($sec / 3600);
        if ($hour == 0) {
            $min = floor($sec / 60);
            if ($min == 0) {
                $res = '刚刚';
            } else {
                $res = $min . '分钟前';
            }
        } elseif ($hour < 24) {
            $res = $hour . '小时前';
        } elseif ($hour < (24*10)) {
            $res = $hour . '天前';
        } else {
            $res = date($formate, $datetemp);
        }

        return $res;
    }


    /**
     * 获取指定月份的天数
     * @param int $month
     * @param int $year
     * @return int
     */
    public static function getMonthDays($month=null, $year=null) {
        $months_map = [1=>31, 3=>31, 4=>30, 5=>31, 6=>30, 7=>31, 8=>31, 9=>30, 10=>31, 11=>30, 12=>31];
        if(!is_numeric($month) || empty($month)) $month = date('n');
        if(!is_numeric($year) || empty($year)) $year = date('Y');

        if (array_key_exists($month, $months_map)) {
            return $months_map[$month];
        } else {
            if ($year % 100 === 0) {
                if ($year % 400 === 0) {
                    return 29;
                } else {
                    return 28;
                }
            }
            else if ($year % 4 === 0) {
                return 29;
            }
            else {
                return 28;
            }
        }
    }


    /**
     * 将秒数转换为时间字符串
     * 如：
     * 10 将转换为 00:10，
     * 120 将转换为 02:00，
     * 3601 将转换为 01:00:01
     *
     * @param int $second 秒数
     * @return string
     */
    public static function second2time($second=0) {
        $second = intval($second);
        if (!$second) return '';

        $hours = floor($second / 3600);
        $hours = $hours ? str_pad($hours, 2, '0', STR_PAD_LEFT) : 0;
        $second = $second % 3600;
        $minutes = floor($second / 60);
        $minutes = str_pad($minutes, 2, '0', STR_PAD_LEFT);
        $seconds = $second % 60;
        $seconds = str_pad($seconds, 2, '0', STR_PAD_LEFT);

        return implode(':', $hours ? compact('hours', 'minutes', 'seconds') : compact('minutes', 'seconds'));
    }


    /**
     * 获取时间微秒部分
     * @return float
     */
    public static function getMicrotime() {
        list($usec, ) = explode(" ", microtime());
        return ((float)$usec * pow(10,6));
    }







}