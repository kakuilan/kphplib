<?php
/**
 * Copyright (c) 2016 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2016/12/25
 * Time: 16:56
 * Desc: -数组助手类
 */


namespace Lkk\Helpers;

class ArrayHelper {


    /**
     * 检查字符串$string是否包含数组$arr的元素之一
     * @param string $string
     * @param array $arr
     * @param bool $returnvalue 返回匹配的字符串还是返回布尔值
     * @param bool $case 是否检查大小写
     * @return bool
     */
    public static function dstrpos($string, $arr, $returnvalue = false, $case = false) {
        if(empty($string)) return false;
        foreach((array)$arr as $v) {
            if($case ? strpos($string, $v) !== false : stripos($string, $v) !== false) {
                $return = $returnvalue ? $v : true;
                return $return;
            }
        }
        return false;
    }


    /**
     * 对多维数组进行排序
     * @param array $multi_array
     * @param string $sort_key 排序键值
     * @param int $sort 排序类型:SORT_DESC/SORT_ASC
     * @return array|bool
     */
    public static function multiArraySort($multi_array=[], $sort_key='', $sort = SORT_DESC) {
        if (is_array($multi_array)) {
            foreach ($multi_array as $row_array) {
                if (is_array($row_array)) {
                    $key_array[] = $row_array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_array, $sort, $multi_array);
        return $multi_array;
    }


    /**
     * 多维数组去重
     * @param array $multi_array
     * @param bool $keepKey 是否保留键值
     * @return array
     */
    public static function multiArrayUnique($multi_array=[], $keepKey=false){
        $has_arr = array();
        $new_arr = array();

        foreach($multi_array as $k=>$v){
            $hash = md5(json_encode($v));
            if(in_array($hash, $has_arr)){
                continue;
            }else{
                $has_arr[] = $hash;
                if($keepKey){
                    $new_arr[$k] = $v;
                }else{
                    $new_arr[] = $v;
                }
            }
        }

        unset($has_arr, $multi_array);
        return $new_arr;
    }


    /**
     * 二维数组按指定的键值排序
     * @param array $arr
     * @param string $keys 排序键值
     * @param string $type 排序方式:desc/asc
     * @param bool $keepKey 是否保留键值
     * @return array
     */
    public static function arraySort($arr=[], $keys='', $type = 'desc', $keepKey=false) {
        $keysvalue = $new_array = array();
        foreach ($arr as $k => $v) {
            $keysvalue[$k] = $v[$keys];
        }
        if (strtolower($type) == 'asc') {
            asort($keysvalue);
        }elseif(strtolower($type) == 'desc'){
            arsort($keysvalue);
        } else {
            arsort($keysvalue);
        }
        reset($keysvalue);
        foreach ($keysvalue as $k => $v) {
            if($keepKey){
                $new_array[$k] = $arr[$k];
            }else{
                $new_array[] = $arr[$k];
            }
        }
        return $new_array;
    }


    /**
     * 对数组元素递归求值
     * @param string $filter 回调函数
     * @param array $data 数组
     * @return array
     */
    public static function arrayMapRecursive($filter='', $data=[]) {
        $result = array();
        foreach ($data as $key => $val) {
            $result[$key] = is_array($val)
                ? self::arrayMapRecursive($filter, $val)
                : call_user_func($filter, $val);
        }
        return $result;
    }


    /**
     * 取多维数组的最底层值
     * @param $array
     * @param array $vals
     * @return array
     */
    public static function arrayValuesMulti($array, &$vals=[]) {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                self::arrayValuesMulti($value,$vals);
            }else{
                $vals[] = $value;
            }
        }

        return $vals;
    }


    /**
     * 对象转数组
     * @param object $obj
     * @return array
     */
    public static function object2Array($obj){
        $arr = is_object($obj) ? get_object_vars($obj) : $obj;
        if(is_array($arr)){
            return array_map(__METHOD__, $arr);
        }else{
            return $arr;
        }
    }


    /**
     * 数组转对象
     * @param array $arr
     * @return array|object
     */
    public static function arrayToObject($arr=[]){
        if(is_array($arr)){
            return (object) array_map(__METHOD__, $arr);
        }else{
            return $arr;
        }
    }


    /**
     * 数组元素组合
     * @param array $arr 数组
     * @param int $m 组合长度
     * @param string $separator 分隔符
     *
     * @return array
     */
    private static function _combination($arr=[], $m=0, $separator='') {
        $result = array();
        if ($m ==1) return $arr;

        if ($m == count($arr)){
            $result[] = implode($separator, $arr);
            return $result;
        }

        $temp_firstelement = $arr[0];
        unset($arr[0]);
        $arr = array_values($arr);
        $temp_list1 = self::_combination($arr, ($m-1), $separator);

        foreach ($temp_list1 as $s){
            $s = $temp_firstelement. $separator. $s;
            $result[] = $s;
        }

        $temp_list2 = self::_combination($arr, $m, $separator);
        foreach ($temp_list2 as $s){
            $result[] = $s;
        }

        return $result;
    }


    /**
     * 排列组合数组的元素
     * @param array $arr 要排列组合的数组
     * @param string $separator 分隔符
     *
     * @return array
     */
    public static function getCombinationToString($arr=[], $separator=''){
        $res = [];
        for($i=1,$count=count($arr);$i<=$count;$i++) {
            $newItems = self::_combination($arr, $i, $separator);
            $res = array_merge($res, $newItems);
        }

        return $res;
    }


    /**
     * 从数组中搜索对应元素(单个)
     * @param array $data 要搜索的数组
     * @param array $condition 条件
     * @param bool $delSource 取出后,是否删除原数组的元素
     * @return bool|mixed
     */
    public static function arraySearchItem(&$data = [], $condition = [], $delSource=false) {
        if (empty($data)) return false;
        $match = count($condition);
        foreach ($data as $j=>$item) {
            $check = 0;
            foreach ($condition as $k => $v) {
                if (isset($item[$k]) && $item[$k] == $v) $check++;
            }

            if ($check == $match) {
                if($delSource) unset($data[$j]);
                return $item;
            }
        }

        return false;
    }


    /**
     * 从数组中搜索对应元素(多个)
     * @param array $data 要搜索的数组
     * @param array $condition 条件
     * @param bool $delSource 取出后,是否删除原数组的元素
     * @return array|bool
     */
    public static function arraySearchMutilItem(&$data = [], $condition = [], $delSource=false) {
        if (empty($data)) return false;
        $res = array();
        $match = count($condition);
        foreach ($data as $j=>$item) {
            $check = 0;
            foreach ($condition as $k => $v) {
                if (isset($item[$k]) && $item[$k] == $v) $check++;
            }

            if ($check == $match) {
                $res[] = $item;
                if($delSource) unset($data[$j]);
            }
        }

        return $res;
    }


}