<?php
/**
 * Copyright (c) 2016 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2016/12/25
 * Time: 17:05
 * Desc: -验证助手类
 */


namespace Lkk\Helpers;

class ValidateHelper {


    /**
     * 是否整数
     * @param $val
     * @return bool|int
     */
    public static function isInteger($val) {
        if (!is_scalar($val) || is_bool($val)) {
            return false;
        }
        if (is_numeric($val) && is_float($val + 0) && ($val + 0) > PHP_INT_MAX) {
            return false;
        }
        return is_float($val) ? false : preg_match('~^((:?+|-)?[0-9]+)$~', $val);
    }


    /**
     * 是否浮点数
     * @param $val
     * @return bool
     */
    public static function isFloat($val) {
        if (!is_scalar($val)) {
            return false;
        }
        return is_numeric($val) && is_float($val + 0);
    }


    /**
     * 是否奇数
     * @param $num
     * @return int
     */
    public static function isOdd($num) {
        return (is_numeric($num) & ($num&1) );
    }


    /**
     * 是否偶数
     * @param $num
     * @return int
     */
    public static function isEven($num) {
        return (is_numeric($num) & (!($num&1)) );
    }


    /**
     * 是否JSON格式
     * @param string $str
     *
     * @return bool
     */
    public static function isJson($str='') {
        @json_decode($str);
        return (json_last_error() == JSON_ERROR_NONE);
    }


    /**
     * 是否邮箱
     * @param string $email
     * @param int $minLen 字符串最小长度
     * @param int $maxLen 字符串最大长度
     * @return bool
     */
    public static function isEmail($email = '', $minLen=6, $maxLen=40) {
        $len = strlen($email);
        return $minLen <=$len && $len <= $maxLen
            && filter_var($email,FILTER_VALIDATE_EMAIL)
            && preg_match("/^[\w\-\.]+@[\w\-\.]+(\.\w+)+$/", $email);
    }


    /**
     * 是否手机号码
     * @param $str
     * @return int
     */
    public static function isMobile($str) {
        return preg_match("/^1[3456789]\d{9}$/", $str);
    }


    /**
     * 是否固定电话/400
     * @param $str
     * @return bool
     */
    public static function isTel($str) {
        return preg_match('/^(010|02\d{1}|0[3-9]\d{2})-\d{7,9}(-\d+)?$/', $str) || preg_match('/^400(-\d{3,4}){2}$/', $str);
    }


    /**
     * 是否电话号码(手机或固话)
     * @param $str
     * @return bool
     */
    public static function isPhone($str){
        return (self::isMobile($str) || self::isTel($str));
    }


    /**
     * 是否url
     * @param $url
     * @return bool
     */
    public static function isUrl($url){
        return filter_var($url, FILTER_VALIDATE_URL)
            && preg_match('/^http[s]?:\/\/'.
                '(([0-9]{1,3}\.){3}[0-9]{1,3}'. // IP形式的URL- 199.194.52.184
                '|'. // 允许IP和DOMAIN（域名）
                '([0-9a-z_!~*\'()-]+\.)*'. // 三级域验证- www.
                '([0-9a-z][0-9a-z-]{0,61})?[0-9a-z]\.'. // 二级域验证
                '[a-z]{2,6})'.  // 顶级域验证.com or .museum
                '(:[0-9]{1,4})?'.  // 端口- :80
                '((\/\?)|'.  // 如果含有文件对文件部分进行校验
                '(\/[0-9a-zA-Z_!~\*\'\(\)\.;\?:@&=\+\$,%#-\/]*)?)$/',
                $url) == 1;
    }


    /**
     * 是否身份证
     * @param string $str
     * @return bool
     */
    public static function isCreditNo($str) {
        $city = array(11=>"北京",12=>"天津",13=>"河北",14=>"山西",15=>"内蒙古",21=>"辽宁",22=>"吉林",23=>"黑龙江",31=>"上海",32=>"江苏",33=>"浙江",34=>"安徽",35=>"福建",36=>"江西",37=>"山东",41=>"河南",42=>"湖北",43=>"湖南",44=>"广东",45=>"广西",46=>"海南",50=>"重庆",51=>"四川",52=>"贵州",53=>"云南",54=>"西藏",61=>"陕西",62=>"甘肃",63=>"青海",64=>"宁夏",65=>"新疆",71=>"台湾",81=>"香港",82=>"澳门",91=>"国外");

        //18位或15位
        if (!preg_match('/^([\d]{17}[xX\d]|[\d]{15})$/', $str)) return false;

        //省市代码
        if (!in_array(substr($str, 0, 2), array_keys($city))) return false;

        $len = strlen($str);

        //将15位身份证升级到17位
        if($len == 15 ){
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($str, 12, 3), array('996', '997', '998', '999')) !== false){
                $str = substr($str, 0, 6) . '18'. substr($str, 6, 9);
            }else{
                $str = substr($str, 0, 6) . '19'. substr($str, 6, 9);
            }
        }

        //检查生日
        $birthday = substr($str, 6, 4) . '-' . substr($str, 10, 2) . '-' . substr($str, 12, 2);
        if (date('Y-m-d', strtotime($birthday)) != $birthday) return false;

        //18位身份证需要验证最后一位校验位
        if($len == 18) {
            //∑(ai×Wi)(mod 11)
            //加权因子
            $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
            //校验位对应值
            $parity = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            $sum = 0;
            for($i=0; $i<17; $i++) {
                $sum += substr($str, $i, 1) * $factor[$i];
            }

            $mod = $sum % 11;
            if(strtoupper(substr($str, 17, 1)) != $parity[$mod]) {
                return false;
            }
        }

        return true;
    }


    /**
     * 检查字符串是否是UTF8编码
     * @param $str
     * @return bool
     */
    public static function isUtf8($str) {
        $c=0; $b=0;
        $bits=0;
        $len=strlen($str);
        for($i=0; $i<$len; $i++){
            $c=ord($str[$i]);
            if($c > 128){
                if(($c >= 254)) return false;
                elseif($c >= 252) $bits=6;
                elseif($c >= 248) $bits=5;
                elseif($c >= 240) $bits=4;
                elseif($c >= 224) $bits=3;
                elseif($c >= 192) $bits=2;
                else return false;
                if(($i+$bits) > $len) return false;
                while($bits > 1){
                    $i++;
                    $b=ord($str[$i]);
                    if($b < 128 || $b > 191) return false;
                    $bits--;
                }
            }
        }
        return true;
    }


    /**
     * 是否全是中文字符串
     * @param $string
     * @return bool
     */
    public static function isChinese($string) {
        if (preg_match("/^[\\x{4e00}-\\x{9fa5}]+$/u", $string)) {
            return true;
        }

        return false;
    }


    /**
     * 是否含有中文字符
     * @param $string
     * @return bool
     */
    public static function hasChinese($string) {
        if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $string, $match)) {
            return true;
        }

        return false;
    }


    /**
     * 是否英文字符串
     * @param $string
     * @return bool
     */
    public static function isEnglish($string) {
        if (preg_match("/^[a-z]+$/i", $string)) {
            return true;
        }

        return false;
    }


    /**
     * 是否包含英文字符
     * @param $string
     * @return bool
     */
    public static function hasEnglish($string) {
        if (preg_match("/[a-z]+/i", $string)) {
            return true;
        }

        return false;
    }


    /**
     * 检查字符串是否日期格式(且转换为时间戳)
     * @param $string
     * @return int
     */
    public static function isDate2time($string){
        /* 匹配
        0000
        0000-00
        0000/00
        0000-00-00
        0000/00/00
        0000-00-00 00
        0000/00/00 00
        0000-00-00 00:00
        0000/00/00 00:00
        0000-00-00 00:00:00
        0000/00/00 00:00:00 */
        $string = str_replace('/','-',$string);
        $check = preg_match("/^[0-9]{4}(|\-[0-9]{2}(|\-[0-9]{2}(|\s+[0-9]{2}(|:[0-9]{2}(|:[0-9]{2})))))$/",$string);
        if(!$check){
            return 0;
        }

        $string .= substr('1970-00-00 00:00:00', strlen($string), 19);
        $unixTime = strtotime($string);
        if(!$unixTime) $unixTime=0;

        return $unixTime;
    }


    /**
     * 是否iPhone
     * @param string $agent 客户端头信息
     * @return bool
     */
    public static function isiPhone($agent='') {
        return stripos($agent, 'iPhone') !== false;
    }


    /**
     * 是否iPad
     * @param string $agent 客户端头信息
     * @return bool
     */
    public static function isiPad($agent='') {
        return stripos($agent, 'iPad') !== false;
    }


    /**
     * 是否iOS设备
     * @param string $agent 客户端头信息
     * @return bool
     */
    public static function isiOS($agent='') {
        return self::isiPhone($agent) || self::isiPad($agent);
    }


    /**
     * 是否Android设备
     * @param string $agent 客户端头信息
     * @return bool
     */
    public static function isAndroid($agent='') {
        return stripos($agent, 'Android') !== false;
    }


    /**
     * 用于判断文件后缀是否是图片
     * @param string $file 文件路径，通常是$_FILES['file']['tmp_name']
     * @return bool
     */
    public static function isImage($file=''){
        $fileextname = strtolower(substr(strrchr(rtrim(basename($file),'?'),"."),1,4));
        if(in_array($fileextname,array('jpg','jpeg','gif','png','bmp'))){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 用于判断文件后缀是否是PHP、EXE类的可执行文件
     * @param string $file 文件路径
     * @return bool
     */
    public static function isExecFile($file){
        $fileextname = strtolower(substr(strrchr(rtrim(basename($file),'?'), "."),1,4));
        if(in_array($fileextname,array('php','php3','php4','php5','exe','sh','py'))){
            return true;
        }else{
            return false;
        }
    }


    /**
     * 判断字符串是否IPv4格式
     * @param string $ip
     * @return bool|int
     */
    public static function isIpv4($ip=''){
        $ipArr = explode('.', $ip);
        if(empty($ipArr)){
            return false;
        }else{
            foreach($ipArr as $v){
                if(!is_numeric($v) || $v>255){
                    return false;
                }
            }

        }

        return preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/",$ip);
    }


    /**
     * 是否window系统
     * @return bool
     */
    public static function isWinOS() {
        return strtoupper(substr(PHP_OS,0,3))==='WIN';
    }


    /**
     * 端口是否已绑定
     * @param string $hostname 主机/IP
     * @param int    $port 端口
     * @param int    $timeout 超时
     *
     * @return bool
     */
    public static function isPortBinded($hostname = '127.0.0.1', $port = 80, $timeout = 5) {
        $res = false; //端口未绑定
        $fp	= @fsockopen($hostname, $port, $errno, $errstr, $timeout);
        if($errno == 0 && $fp!=false){
            fclose($fp);
            $res = true; //端口已绑定
        }

        return $res;
    }


}