<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/8/13
 * Time: 15:28
 * Desc: -通用助手类
 */


namespace Lkk\Helpers;

use Lkk\Helpers\ArrayHelper;

class CommonHelper {


    /**
     * 获取当前时间戳的毫秒
     * @return float
     */
    public static function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($t1) + floatval($t2)) * 1000);
    }


    /**
     * 检查文件或目录是否可写
     * @param string $file
     * @return bool
     */
    public static function isReallyWritable($file='') {
        // If we're on a Unix server with safe_mode off we call is_writable
        if (DIRECTORY_SEPARATOR == '/' and ini_get('safe_mode') == false){
            return is_writable($file);
        }

        // For windows servers and safe_mode "on" installations we'll actually
        // write a file then read it.  Bah...
        if (is_dir($file)){
            $file = rtrim($file, '/') . '/_isReallyWritable_' . md5(mt_rand(1,10000));

            if (! file_put_contents($file, 'php isReallyWritable() test file')){
                return false;
            }else{
                unlink($file);
            }

            return true;
        }elseif ( ($fp = fopen($file, 'w+')) === false) {
            return false;
        }
        fclose($fp);

        return true;
    }


    /**
     * 错误捕获
     * @param $logFile
     *
     * @return null
     */
    public static function errorHandler($logFile) {
        if(empty($logFile)) $logFile = '/tmp/phperr_'. date('Ymd').'.log';

        ini_set('log_errors', 1); //设置错误信息输出到文件
        ini_set('ignore_repeated_errors', 1);//不重复记录出现在同一个文件中的同一行代码上的错误信息

        $user_defined_err = error_get_last();//获取最后发生的错误
        if ($user_defined_err['type'] > 0) {
            switch ($user_defined_err['type']) {
                case 1:
                    $user_defined_errType = '致命的运行时错误(E_ERROR)';
                    break;
                case 2:
                    $user_defined_errType = '非致命的运行时错误(E_WARNING)';
                    break;
                case 4:
                    $user_defined_errType = '编译时语法解析错误(E_PARSE)';
                    break;
                case 8:
                    $user_defined_errType = '运行时提示(E_NOTICE)';
                    break;
                case 16:
                    $user_defined_errType = 'PHP内部错误(E_CORE_ERROR)';
                    break;
                case 32:
                    $user_defined_errType = 'PHP内部警告(E_CORE_WARNING)';
                    break;
                case 64:
                    $user_defined_errType = 'Zend脚本引擎内部错误(E_COMPILE_ERROR)';
                    break;
                case 128:
                    $user_defined_errType = 'Zend脚本引擎内部警告(E_COMPILE_WARNING)';
                    break;
                case 256:
                    $user_defined_errType = '用户自定义错误(E_USER_ERROR)';
                    break;
                case 512:
                    $user_defined_errType = '用户自定义警告(E_USER_WARNING)';
                    break;
                case 1024:
                    $user_defined_errType = '用户自定义提示(E_USER_NOTICE)';
                    break;
                case 2048:
                    $user_defined_errType = '代码提示(E_STRICT)';
                    break;
                case 4096:
                    $user_defined_errType = '可以捕获的致命错误(E_RECOVERABLE_ERROR)';
                    break;
                case 8191:
                    $user_defined_errType = '所有错误警告(E_ALL)';
                    break;
                default:
                    $user_defined_errType = '未知类型';
                    break;
            }

            $msg = sprintf('[%s] %s %s %s line:%s',
                date("Y-m-d H:i:s"),
                $user_defined_errType,
                $user_defined_err['message'],
                $user_defined_err['file'],
                $user_defined_err['line']);

            //必须显式地记录错误
            error_log($msg."\r\n", 3, $logFile);
        }

        return null;
    }



    /**
     * 获取浏览器信息
     * @param bool $returnAll 是否返回所有信息:否-只返回浏览器名称;是-返回相关数组
     * @return array|string
     */
    public static function getBrowser($returnAll=false){
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $bname = 'Unknown';
        $platform = 'Unknown';
        $version= "";

        //First get the platform?
        if (preg_match('/linux/i', $u_agent)) {
            $platform = 'Linux';
        }elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
            $platform = 'MAC';
        }elseif (preg_match('/windows|win32/i', $u_agent)) {
            $platform = 'Windows';
        }elseif (preg_match('/unix/i',$u_agent)) {
            $platform = 'Unix';
        }elseif (preg_match('/bsd/i',$u_agent)) {
            $platform = 'BSD';
        }elseif (preg_match('/iPhone/i',$u_agent)) {
            $platform = 'iPhone';
        }elseif (preg_match('/iPad/i',$u_agent)) {
            $platform = 'iPad';
        }elseif (preg_match('/iPod/i',$u_agent)) {
            $platform = 'iPod';
        }elseif (preg_match('/android/i',$u_agent)) {
            $platform = 'Android';
        }

        // Next get the name of the useragent yes seperately and for good reason
        if((preg_match('/MSIE/i',$u_agent) || strpos($u_agent,'rv:11.0')) && !preg_match('/Opera/i',$u_agent)) {
            $bname = 'Internet Explorer';
            $ub = "MSIE";
        }elseif(preg_match('/Firefox/i',$u_agent)) {
            $bname = 'Mozilla Firefox';
            $ub = "Firefox";
        }elseif(preg_match('/Edge/i',$u_agent)) {//win10 Edge浏览器 添加了chrome内核标记 在判断Chrome之前匹配
            $bname = 'Microsoft Edge';
            $ub = "Edge";
        }elseif(preg_match('/Chrome/i',$u_agent)) {
            $bname = 'Google Chrome';
            $ub = "Chrome";
        }elseif(preg_match('/Safari/i',$u_agent)) {
            $bname = 'Apple Safari';
            $ub = "Safari";
        }elseif(preg_match('/Opera/i',$u_agent)) {
            $bname = 'Opera';
            $ub = "Opera";
        }elseif(preg_match('/Netscape/i',$u_agent)) {
            $bname = 'Netscape';
            $ub = "Netscape";
        }elseif(preg_match('/Maxthon/i',$u_agent)) {
            $bname = 'Maxthon';
            $ub = "Maxthon";
        }elseif(preg_match('/Lynx/i',$u_agent)) {
            $bname = 'Lynx';
            $ub = "Lynx";
        }elseif(preg_match('/w3m/i',$u_agent)) {
            $bname = 'w3m';
            $ub = "w3m";
        }

        // finally get the correct version number
        $known = array('Version', $ub, 'other');
        $pattern = '#(?<browser>' . join('|', $known) . ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        if (!preg_match_all($pattern, $u_agent, $matches)) {
            // we have no matching number just continue
        }

        // see how many we have
        $i = count($matches['browser']);
        if ($i != 1) {
            //we will have two since we are not using 'other' argument yet
            //see if version is before or after the name
            if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
                $version= $matches['version'][0];
            }
            else {
                $version= $matches['version'][1];
            }
        }
        else {
            $version= $matches['version'][0];
        }

        // check if we have a number
        if ($version==null || $version=="") {$version="?";}
        $res = array(
            'userAgent' => $u_agent,	//用户客户端信息
            'name'      => $bname,		//浏览器名称
            'version'   => $version,	//浏览器版本
            'platform'  => $platform,	//使用平台
            'pattern'   => $pattern	    //匹配正则
        );

        return $returnAll ? $res : $bname;
    }



    /**
     * 获取客户端操作系统
     * @return string
     */
    public static function getClientOS() {
        $u_agent = $_SERVER['HTTP_USER_AGENT'];
        $OS = 'Unknown';
        if (preg_match('/win/i',$u_agent)) {
            $OS = 'Windows';
        }elseif (preg_match('/mac/i',$u_agent)) {
            $OS = 'MAC';
        }elseif (preg_match('/linux/i',$u_agent)) {
            $OS = 'Linux';
        }elseif (preg_match('/unix/i',$u_agent)) {
            $OS = 'Unix';
        }elseif (preg_match('/bsd/i',$u_agent)) {
            $OS = 'BSD';
        }elseif (preg_match('/iPhone|iPad|iPod/i',$u_agent)) {
            $OS = 'iOS';
        }elseif (preg_match('/android/i',$u_agent)) {
            $OS = 'Android';
        }

        return $OS;
    }



    /**
     * 是否移动请求
     * @return bool|int
     */
    public static function isMobileRequest() {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }
        // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        elseif (isset($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'],"wap")) {
            return true;
        }
        // 检查浏览器是否接受 WML
        elseif(strpos(strtoupper($_SERVER['HTTP_ACCEPT']),"VND.WAP.WML") > 0){
            return true;
        }

        static $touchbrowser_list = array('iphone', 'android', 'phone', 'mobile', 'wap', 'netfront', 'java', 'opera mobi', 'opera mini','ucweb', 'windows ce', 'symbian', 'series', 'webos', 'sony', 'blackberry', 'dopod', 'nokia', 'samsung','palmsource', 'xda', 'pieplus', 'meizu', 'midp', 'cldc', 'motorola', 'foma', 'docomo', 'up.browser','up.link', 'blazer', 'helio', 'hosin', 'huawei', 'novarra', 'coolpad', 'webos', 'techfaith', 'palmsource','alcatel', 'amoi', 'ktouch', 'nexian', 'ericsson', 'philips', 'sagem', 'wellcom', 'bunjalloo', 'maui', 'smartphone','iemobile', 'spice', 'bird', 'zte-', 'longcos', 'pantech', 'gionee', 'portalmmm', 'jig browser', 'hiptop','benq', 'haier', '^lct', '320x320', '240x320', '176x220', 'windows phone');

        static $wmlbrowser_list = array('cect', 'compal', 'ctl', 'lg', 'nec', 'tcl', 'alcatel', 'ericsson', 'bird', 'daxian', 'dbtel', 'eastcom','pantech', 'dopod', 'philips', 'haier', 'konka', 'kejian', 'lenovo', 'benq', 'mot', 'soutec', 'nokia', 'sagem', 'sgh','sed', 'capitel', 'panasonic', 'sonyericsson', 'sharp', 'amoi', 'panda', 'zte');

        static $pad_list = array('ipad');

        static $other_list = array('htc','ipod','palm','openwave','nexus one','Cellphone','Xphone','kindle','mmp','pocket','ppc','sqh','spv','treo','vodafone');

        $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);

        if(ArrayHelper::dstrpos($useragent, $pad_list)) {
            return false;
        }
        if(($v = ArrayHelper::dstrpos($useragent, $touchbrowser_list, true))){
            return 3;
        }
        if(($v = ArrayHelper::dstrpos($useragent, $wmlbrowser_list))) {
            return 2; //wml版
        }
        if(($v = ArrayHelper::dstrpos($useragent, $other_list, true))){
            return 1;
        }

        $brower = array('mozilla', 'chrome', 'safari', 'opera', 'm3gate', 'winwap', 'openwave', 'myop');
        if(ArrayHelper::dstrpos($useragent, $brower)) return false;

        return false;
    }



    /**
     * 获取客户端IP
     * @return string
     */
    public static function getClientIp() {
        if (isset($_SERVER)){
            //获取代理ip
            if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]) && preg_match_all('#(\d+\.){3}\d+#', $_SERVER['HTTP_X_FORWARDED_FOR'], $matches)){
                foreach ($matches[0] AS $xip) {
                    if (!preg_match('#^(10|172\.16|192\.168)\.#', $xip)) {
                        $ip = $xip;
                        break;
                    }
                }
            } else if (isset($_SERVER["HTTP_CLIENT_IP"])) {
                $ip = $_SERVER["HTTP_CLIENT_IP"];
            } else {
                $ip = $_SERVER["REMOTE_ADDR"];
            }
        } else {
            if (getenv("HTTP_X_FORWARDED_FOR")){
                $ip = getenv("HTTP_X_FORWARDED_FOR");
            } else if (getenv("HTTP_CLIENT_IP")) {
                $ip = getenv("HTTP_CLIENT_IP");
            } else {
                $ip = getenv("REMOTE_ADDR");
            }
        }

        preg_match("/[\d\.]{7,15}/", $ip, $ipmatches);
        $ip = isset($ipmatches[0]) ? $ipmatches[0] : '0.0.0.0';

        return $ip;
    }


    /**
     * 获取服务器IP
     * @return string
     */
    public static function getServerIP(){
        /*static $serverIp = NULL;
        if($serverIp !== NULL) return $serverIp;*/

        if(isset($_SERVER)){
            if(isset($_SERVER['SERVER_ADDR'])){
                $serverIp = $_SERVER['SERVER_ADDR'];
            }else{
                $serverIp = $_SERVER['LOCAL_ADDR'];
            }
        }elseif($serverIp = getenv('SERVER_ADDR')){

        }else{
            $serverIp = gethostbyname(gethostname());
        }

        if(!filter_var($serverIp, FILTER_VALIDATE_IP)){
            $serverIp = '0.0.0.0';
        }

        return $serverIp;
    }


    /**
     * 获取域名
     * @param string $url
     * @param bool $firstLevel 是否获取一级域名,如:abc.test.com取test.com
     * @return null|string
     */
    public static function getDomain($url='', $firstLevel=false){
        if(empty($url)) $url = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';

        $parse = parse_url($url);
        $domain = null;
        if(isset($parse['host'])) $domain = $parse['host'];

        if($firstLevel) {
            $tmpArr = explode('.', $domain);
            $size = count($tmpArr);
            if($size>=2){
                $domain = $tmpArr[$size-2] . '.' . end($tmpArr);
            }
        }

        return $domain;
    }


    /**
     * 获取当前页面完整URL地址
     * @return string
     */
    public static function getUrl() {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] :
            $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.$_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
    }


    /**
     * 获取URI
     * @return string
     */
    public static function getUri() {
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
            return $uri;
        }
        if (isset($_SERVER['argv'])) {
            $uri = $_SERVER['PHP_SELF'] . "?" . $_SERVER['argv'][0];
            return $uri;
        }
        $uri = $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'];
        return $uri;
    }




}