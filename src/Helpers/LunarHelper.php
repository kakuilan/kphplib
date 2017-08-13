<?php
/**
 * Copyright (c) 2016 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2016/12/25
 * Time: 17:03
 * Desc: -农历助手类
 */


namespace Lkk\Helpers;
use Lkk\Helpers\ValidateHelper;

class LunarHelper {

    /**
     * 根据时间串获取星座(时间戳或Y-m-d日期格式)
     * @param string $timeString
     * @return bool|string
     */
    public static function getXingZuo($timeString){
        $res = false;
        if(is_numeric($timeString) && strlen($timeString)>4){
            $timeString = date('Y-m-d H:i:s', $timeString);
        }elseif(is_string($timeString) && !ValidateHelper::isDate2time($timeString) ){
            return $res;
        }

        $month = substr($timeString,5,2); //取出月份
        $day = intval(substr($timeString,8,2)); //取出日期

        switch($month){
            case "01":
                if($day<21){$res='摩羯';}else{$res='水瓶';} break;
            case "02":
                if($day<20){$res='水瓶';}else{$res='双鱼';} break;
            case "03":
                if($day<21){$res='双鱼';}else{$res='牧羊';} break;
            case "04":
                if($day<20){$res='牧羊';}else{$res='金牛';} break;
            case "05":
                if($day<21){$res='金牛';}else{$res='双子';} break;
            case "06":
                if($day<22){$res='双子';}else{$res='巨蟹';} break;
            case "07":
                if($day<23){$res='巨蟹';}else{$res='狮子';} break;
            case "08":
                if($day<23){$res='狮子';}else{$res='处女';} break;
            case "09":
                if($day<23){$res='处女';}else{$res='天秤';} break;
            case "10":
                if($day<24){$res='天秤';}else{$res='天蝎';} break;
            case "11":
                if($day<22){$res='天蝎';}else{$res='射手';} break;
            case "12":
                if($day<22){$res='射手';}else{$res='摩羯';} break;
        }

        return $res;
    }



    /**
     * 根据时间串获取生肖(时间戳或Y-m-d日期格式)
     * @param $timeString
     * @return bool|string
     */
    public static function getShengXiao($timeString){
        $res = false;
        if(is_numeric($timeString) && strlen($timeString)>4){
            $timeString = date('Y-m-d H:i:s', $timeString);
        }elseif(is_string($timeString) && !ValidateHelper::isDate2time($timeString) ){
            return $res;
        }

        $startYear = 1901;
        $endYear = intval(substr($timeString,0,4));
        $x = ($startYear - $endYear) % 12;

        switch($x){
            case 1 : case -11:
            $res = "鼠";
            break;
            case 0:
            $res = "牛";
            break;
            case 11 : case -1:
            $res = "虎";
            break;
            case 10 : case -2:
            $res = "兔";
            break;
            case 9 : case -3:
            $res = "龙";
            break;
            case 8 : case -4:
            $res = "蛇";
            break;
            case 7 : case -5:
            $res = "马";
            break;
            case 6 : case -6:
            $res = "羊";
            break;
            case 5 : case -7:
            $res = "猴";
            break;
            case 4: case -8:
            $res = "鸡";
            break;
            case 3 : case -9:
            $res = "狗";
            break;
            case 2 : case -10:
            $res = "猪";
            break;
        }

        return $res;
    }


    /**
     * 根据时间串获取农历年份/天干地支(时间戳或Y-m-d日期格式)
     * @param $timeString
     * @return bool|string
     */
    public static function getLunarYear($timeString){
        $res = false;
        if(is_numeric($timeString) && strlen($timeString)>4){
            $timeString = date('Y-m-d H:i:s', $timeString);
        }elseif(is_string($timeString) && !ValidateHelper::isDate2time($timeString) ){
            return $res;
        }

        //天干
        $sky = array('庚','辛','壬','癸','甲','乙','丙','丁','戊','己');
        //地支
        $earth = array('申','酉','戌','亥','子','丑','寅','卯','辰','巳','午','未');

        $year = intval(substr($timeString,0,4));
        $diff = $year - 1900 + 40;

        $res = $sky[$diff % 10] . $earth[$diff % 12];
        return $res;
    }



}