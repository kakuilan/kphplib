<?php
/**
 * Copyright (c) 2016 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2016/12/25
 * Time: 17:04
 * Desc: -URL助手类
 */


namespace Lkk\Helpers;

class UrlHelper {

    /**
     * 中文urlencode [对URL中有中文的部分进行编码处理]
     * @param string $url 地址 http://www.abc3210.com/s?wd=博客
     * @return string  编码后的地址 http://www.abc3210.com/s?wd=%E5%8D%9A%20%E5%AE%A2
     */
    public static function cnUrlencode($url) {
        $pregstr = "/[\x{4e00}-\x{9fa5}]+/u"; //UTF-8中文正则
        if (preg_match_all($pregstr, $url, $matchArray)) {//匹配中文，返回数组
            foreach ($matchArray[0] as $key => $val) {
                $url = str_replace($val, urlencode($val), $url); //将转译替换中文
            }
            if (strpos($url, ' ')) {//若存在空格
                $url = str_replace(' ', '%20', $url);
            }
        }
        return $url;
    }


    /**
     * 中文urldecode
     * @param string $source
     * @return string
     */
    public static function cnUrldecode($source){
        $decodedStr = "";
        $pos = 0;
        $len = strlen ($source);
        while ($pos < $len) {
            $charAt = substr ($source, $pos, 1);
            if ($charAt == '%') {
                $pos++;
                $charAt = substr ($source, $pos, 1);
                if ($charAt == 'u') {
                    // we got a unicode character
                    $pos++;
                    $unicodeHexVal = substr ($source, $pos, 4);
                    $unicode = hexdec ($unicodeHexVal);
                    $entity = "&#". $unicode . ';';
                    $decodedStr .= utf8_encode ($entity);
                    $pos += 4;
                }
                else {
                    // we have an escaped ascii character
                    $hexVal = substr ($source, $pos, 2);
                    $decodedStr .= chr (hexdec ($hexVal));
                    $pos += 2;
                }
            } else {
                $decodedStr .= $charAt;
                $pos++;
            }
        }
        return $decodedStr;
    }


    /**
     * 根据数组的键值对,组建uri
     * @param array $paramArr 参数数组,最多二维
     * @param mixed $replaceKey 要替换的键,数组或字符串
     * @param array $replaceVal 要替换的值
     * @return string
     */
    public static function buildUriByArr($paramArr, $replaceKey=[], $replaceVal=[]){
        if(!empty($replaceKey) && array_key_exists($replaceKey, $paramArr)){
            unset($paramArr[$replaceKey]);
        }

        $res = '';
        foreach($paramArr as $k =>$v){
            if(is_array($v)){
                foreach($v as $v2){
                    $res .= (empty($res)) ? "{$k}[]={$v2}" : "&{$k}[]={$v2}";
                }
            }else{
                $res .= (empty($res)) ? "{$k}={$v}" : "&{$k}={$v}";
            }
        }

        if(!empty($replaceKey)){
            foreach ($replaceKey as $k=>$key) {
                $res .= (empty($res)) ? "{$key}={$replaceVal[$k]}" : "&{$key}={$replaceVal[$k]}";
            }
        }

        return $res;
    }



    /**
     * 转换 URL：从字符串变成超链接
     * @param string $text
     * @return string
     */
    public static function makeUrl2Link($text='') {
        $text = preg_replace('(((f|ht){1}tp://)[-a-zA-Z0-9@:%_+.~#?&//=]+)',
            '<a href="\1" target="_blank">\1</a>', $text);
        $text = preg_replace('([[:space:]()[{}])(www.[-a-zA-Z0-9@:%_+.~#?&//=]+)',
            '\1<a href="http://\2" target="_blank">\2</a>', $text);
        $text = preg_replace('([_.0-9a-z-]+@([0-9a-z][0-9a-z-]+.)+[a-z]{2,3})',
            '<a href="mailto:\1" target="_blank">\1</a>', $text);

        return $text;
    }


    /**
     * 格式化URL
     * @param $url
     * @return mixed
     */
    public static function formatUrl($url) {
        $url = str_replace("\\", "/", $url);
        return preg_replace('/([^:])[\/]{2,}/','$1/', $url);
    }




}