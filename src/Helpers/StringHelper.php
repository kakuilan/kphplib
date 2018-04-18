<?php
/**
 * Copyright (c) 2016 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2016/12/25
 * Time: 17:04
 * Desc: -字符串助手类
 */


namespace Lkk\Helpers;

class StringHelper {


    /**
     * 宽字符串截字函数
     * @param string $str 需要截取的字符串
     * @param int $length 截取的长度
     * @param int $start 开始截取的位置
     * @param string $dot 省略符
     * @return string
     */
    public static function cutStr($str='', $length=1, $start=0, $dot='…'){
        //替换特殊字符实体
        $specialchars = ['&amp;', '&quot;', '&#039;', '&lt;', '&gt;'];
        $entities = ['&', '"', "'", '<', '>'];
        $str = str_replace($specialchars, $entities, $str);

        if (function_exists('mb_get_info')) {
            $iLength = mb_strlen($str, 'utf-8');
            $str = mb_substr($str, $start, $length, 'utf-8');
            return ($length < $iLength - $start) ? $str . $dot : $str;
        } else {
            preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $str, $info);
            $str = join('', array_slice($info[0], $start, $length));
            return ($length < (sizeof($info[0]) - $start)) ? $str . $dot : $str;
        }
    }


    /**
     * 获取宽字符串长度函数
     * @param string $str
     * @param bool $filter 是否过滤html/php标签
     * @return int
     */
    public static function strLeng($str='', $filter = false){
        if ($filter) {
            $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
            $str = strip_tags($str);
        }

        if (function_exists('mb_get_info')) {
            return mb_strlen($str, 'utf-8');
        } else {
            preg_match_all("/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|\xe0[\xa0-\xbf][\x80-\xbf]|[\xe1-\xef][\x80-\xbf][\x80-\xbf]|\xf0[\x90-\xbf][\x80-\xbf][\x80-\xbf]|[\xf1-\xf7][\x80-\xbf][\x80-\xbf][\x80-\xbf]/", $str, $info);
            return sizeof($info[0]);
        }
    }


    /**
     * 生成简单的随机字符串(可用来自动生成密码)
     * @param int $length 字符串长度
     * @param bool $specialChars 是否有特殊字符
     * @return string
     */
    public static function randSimple($length=6, $specialChars=false){
        $chars = 'abcdefghijklmnpqrstuvwxyzABCDEFGHIJKLMNPQRSTUVWXYZ123456789';
        if ($specialChars) {
            $chars .= '!@#$%^&*()_+-=`~[]{}|<>?:';
        }

        $result = '';
        $max = strlen($chars) - 1;
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[rand(0, $max)];
        }
        return $result;
    }


    /**
     * 生成随机数字
     * @param int $length 长度
     * @return string
     */
    public static function randNumber($length =6){
        if($length <= 10) {
            $arr = range(0, 9);
        }else{
            $arr = range(0, pow(10, ceil($length / 10)) -1);
        }
        shuffle($arr);
        $str = implode('', $arr);

        return substr($str, 0, $length);
    }


    /**
     * 产生随机字串 (支持中文)
     * @param int $len 长度
     * @param int $type 字串类型:1 不区分大小写的字母, 2 数字, 3 大写字母, 4 小写字母, 5 中文, 0 混合
     * @param string $addChars 额外的随机字符
     * @return string
     */
    public static function randString($len=6, $type=0, $addChars='') {
        $str ='';
        switch($type) {
            case 1:
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.$addChars;
                break;
            case 2:
                $chars= str_repeat('0123456789',3);
                break;
            case 3:
                $chars='ABCDEFGHIJKLMNOPQRSTUVWXYZ'.$addChars;
                break;
            case 4:
                $chars='abcdefghijklmnopqrstuvwxyz'.$addChars;
                break;
            case 5:
                $chars = "们以我到他会作时要动国产的一是工就年阶义发成部民可出能方进在了不和有大这主中人上为来分生对于学下级地个用同行面说种过命度革而多子后自社加小机也经力线本电高量长党得实家定深法表着水理化争现所二起政三好十战无农使性前等反体合斗路图把结第里正新开论之物从当两些还天资事队批点育重其思与间内去因件日利相由压员气业代全组数果期导平各基或月毛然如应形想制心样干都向变关问比展那它最及外没看治提五解系林者米群头意只明四道马认次文通但条较克又公孔领军流入接席位情运器并飞原油放立题质指建区验活众很教决特此常石强极土少已根共直团统式转别造切九你取西持总料连任志观调七么山程百报更见必真保热委手改管处己将修支识病象几先老光专什六型具示复安带每东增则完风回南广劳轮科北打积车计给节做务被整联步类集号列温装即毫知轴研单色坚据速防史拉世设达尔场织历花受求传口断况采精金界品判参层止边清至万确究书术状厂须离再目海交权且儿青才证低越际八试规斯近注办布门铁需走议县兵固除般引齿千胜细影济白格效置推空配刀叶率述今选养德话查差半敌始片施响收华觉备名红续均药标记难存测士身紧液派准斤角降维板许破述技消底床田势端感往神便贺村构照容非搞亚磨族火段算适讲按值美态黄易彪服早班麦削信排台声该击素张密害侯草何树肥继右属市严径螺检左页抗苏显苦英快称坏移约巴材省黑武培著河帝仅针怎植京助升王眼她抓含苗副杂普谈围食射源例致酸旧却充足短划剂宣环落首尺波承粉践府鱼随考刻靠够满夫失包住促枝局菌杆周护岩师举曲春元超负砂封换太模贫减阳扬江析亩木言球朝医校古呢稻宋听唯输滑站另卫字鼓刚写刘微略范供阿块某功套友限项余倒卷创律雨让骨远帮初皮播优占死毒圈伟季训控激找叫云互跟裂粮粒母练塞钢顶策双留误础吸阻故寸盾晚丝女散焊功株亲院冷彻弹错散商视艺灭版烈零室轻血倍缺厘泵察绝富城冲喷壤简否柱李望盘磁雄似困巩益洲脱投送奴侧润盖挥距触星松送获兴独官混纪依未突架宽冬章湿偏纹吃执阀矿寨责熟稳夺硬价努翻奇甲预职评读背协损棉侵灰虽矛厚罗泥辟告卵箱掌氧恩爱停曾溶营终纲孟钱待尽俄缩沙退陈讨奋械载胞幼哪剥迫旋征槽倒握担仍呀鲜吧卡粗介钻逐弱脚怕盐末阴丰雾冠丙街莱贝辐肠付吉渗瑞惊顿挤秒悬姆烂森糖圣凹陶词迟蚕亿矩康遵牧遭幅园腔订香肉弟屋敏恢忘编印蜂急拿扩伤飞露核缘游振操央伍域甚迅辉异序免纸夜乡久隶缸夹念兰映沟乙吗儒杀汽磷艰晶插埃燃欢铁补咱芽永瓦倾阵碳演威附牙芽永瓦斜灌欧献顺猪洋腐请透司危括脉宜笑若尾束壮暴企菜穗楚汉愈绿拖牛份染既秋遍锻玉夏疗尖殖井费州访吹荣铜沿替滚客召旱悟刺脑措贯藏敢令隙炉壳硫煤迎铸粘探临薄旬善福纵择礼愿伏残雷延烟句纯渐耕跑泽慢栽鲁赤繁境潮横掉锥希池败船假亮谓托伙哲怀割摆贡呈劲财仪沉炼麻罪祖息车穿货销齐鼠抽画饲龙库守筑房歌寒喜哥洗蚀废纳腹乎录镜妇恶脂庄擦险赞钟摇典柄辩竹谷卖乱虚桥奥伯赶垂途额壁网截野遗静谋弄挂课镇妄盛耐援扎虑键归符庆聚绕摩忙舞遇索顾胶羊湖钉仁音迹碎伸灯避泛亡答勇频皇柳哈揭甘诺概宪浓岛袭谁洪谢炮浇斑讯懂灵蛋闭孩释乳巨徒私银伊景坦累匀霉杜乐勒隔弯绩招绍胡呼痛峰零柴簧午跳居尚丁秦稍追梁折耗碱殊岗挖氏刃剧堆赫荷胸衡勤膜篇登驻案刊秧缓凸役剪川雪链渔啦脸户洛孢勃盟买杨宗焦赛旗滤硅炭股坐蒸凝竟陷枪黎救冒暗洞犯筒您宋弧爆谬涂味津臂障褐陆啊健尊豆拔莫抵桑坡缝警挑污冰柬嘴啥饭塑寄赵喊垫丹渡耳刨虎笔稀昆浪萨茶滴浅拥穴覆伦娘吨浸袖珠雌妈紫戏塔锤震岁貌洁剖牢锋疑霸闪埔猛诉刷狠忽灾闹乔唐漏闻沈熔氯荒茎男凡抢像浆旁玻亦忠唱蒙予纷捕锁尤乘乌智淡允叛畜俘摸锈扫毕璃宝芯爷鉴秘净蒋钙肩腾枯抛轨堂拌爸循诱祝励肯酒绳穷塘燥泡袋朗喂铝软渠颗惯贸粪综墙趋彼届墨碍启逆卸航衣孙龄岭骗休借".$addChars;
                break;
            case 0: default :
            // 默认去掉了容易混淆的字符oOLl和数字01，要添加请使用addChars参数
            $chars='ABCDEFGHIJKMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789'.$addChars;
            break;
        }

        if($len>10 ) {//位数过长重复字符串一定次数
            $chars= $type==2 ? str_repeat($chars,$len) : str_repeat($chars,5);
        }
        if($type!=5) {
            $chars   =   str_shuffle($chars);
            $str     =   substr($chars,0,$len);
        }else{
            // 中文随机字
            for($i=0;$i<$len;$i++){
                $str.= self::cutStr($chars, floor(mt_rand(0,mb_strlen($chars,'utf-8')-1)),1);
            }
        }

        return $str;
    }


    /**
     * 自闭合html修复函数
     * 使用方法:
     * $input = '这是一段被截断的html文本<a href="#"';
     * echo fixHtml($input);
     *
     * @param $string
     * @return mixed|string
     */
    public static function fixHtml($string) {
        //关闭自闭合标签
        $startPos = strrpos($string, "<");

        if (false == $startPos) {
            return $string;
        }

        $trimString = substr($string, $startPos);

        if (false === strpos($trimString, ">")) {
            $string = substr($string, 0, $startPos);
        }

        //非自闭合html标签列表
        preg_match_all("/<([_0-9a-zA-Z-\:]+)\s*([^>]*)>/is", $string, $startTags);
        preg_match_all("/<\/([_0-9a-zA-Z-\:]+)>/is", $string, $closeTags);

        if (!empty($startTags[1]) && is_array($startTags[1])) {
            krsort($startTags[1]);
            $closeTagsIsArray = is_array($closeTags[1]);
            foreach ($startTags[1] as $key => $tag) {
                $attrLength = strlen($startTags[2][$key]);
                if ($attrLength > 0 && "/" == trim($startTags[2][$key][$attrLength - 1])) {
                    continue;
                }
                if (!empty($closeTags[1]) && $closeTagsIsArray) {
                    if (false !== ($index = array_search($tag, $closeTags[1]))) {
                        unset($closeTags[1][$index]);
                        continue;
                    }
                }
                $string .= "</{$tag}>";
            }
        }

        return preg_replace("/\<br\s*\/\>\s*\<\/p\>/is", '</p>', $string);
    }


    /**
     * 全角、半角相互转换
     * @param string $str
     * @param int $type $type：取0，半角转全角；取>=1，全角到半角
     * @return mixed
     */
    public static function SBCxDBC($str='', $type=0) {
        $DBC = [
            '０' , '１' , '２' , '３' , '４' ,
            '５' , '６' , '７' , '８' , '９' ,
            'Ａ' , 'Ｂ' , 'Ｃ' , 'Ｄ' , 'Ｅ' ,
            'Ｆ' , 'Ｇ' , 'Ｈ' , 'Ｉ' , 'Ｊ' ,
            'Ｋ' , 'Ｌ' , 'Ｍ' , 'Ｎ' , 'Ｏ' ,
            'Ｐ' , 'Ｑ' , 'Ｒ' , 'Ｓ' , 'Ｔ' ,
            'Ｕ' , 'Ｖ' , 'Ｗ' , 'Ｘ' , 'Ｙ' ,
            'Ｚ' , 'ａ' , 'ｂ' , 'ｃ' , 'ｄ' ,
            'ｅ' , 'ｆ' , 'ｇ' , 'ｈ' , 'ｉ' ,
            'ｊ' , 'ｋ' , 'ｌ' , 'ｍ' , 'ｎ' ,
            'ｏ' , 'ｐ' , 'ｑ' , 'ｒ' , 'ｓ' ,
            'ｔ' , 'ｕ' , 'ｖ' , 'ｗ' , 'ｘ' ,
            'ｙ' , 'ｚ' , '－' , '　' , '：' ,
            '．' , '，' , '／' , '％' , '＃' ,
            '！' , '＠' , '＆' , '（' , '）' ,
            '＜' , '＞' , '＂' , '＇' , '？' ,
            '［' , '］' , '｛' , '｝' , '＼' ,
            '｜' , '＋' , '＝' , '＿' , '＾' ,
            '＄' , '～' , '｀'
        ];

        $SBC = [ // 半角
            '0', '1', '2', '3', '4',
            '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E',
            'F', 'G', 'H', 'I', 'J',
            'K', 'L', 'M', 'N', 'O',
            'P', 'Q', 'R', 'S', 'T',
            'U', 'V', 'W', 'X', 'Y',
            'Z', 'a', 'b', 'c', 'd',
            'e', 'f', 'g', 'h', 'i',
            'j', 'k', 'l', 'm', 'n',
            'o', 'p', 'q', 'r', 's',
            't', 'u', 'v', 'w', 'x',
            'y', 'z', '-', ' ', ':',
            '.', ',', '/', '%', '#',
            '!', '@', '&', '(', ')',
            '<', '>', '"', '\'','?',
            '[', ']', '{', '}', '\\',
            '|', '+', '=', '_', '^',
            '$', '~', '`'
        ];

        if ($type == 0) {
            return str_replace($SBC, $DBC, $str);  // 半角到全角
        } else{
            return str_replace($DBC, $SBC, $str);  // 全角到半角
        }
    }


    /**
     * 获取相似度最高的字符串
     * @param string $input 要比较的字符串
     * @param array $words 要查找的字符串数组
     * @return mixed|null
     */
    public static function getClosestWord($input='', $words=[]) {
        $shortest = -1;
        $closest = null;
        foreach ($words as $word) {
            $lev = levenshtein($input, $word);
            if ($lev == 0) { //完全相等
                $closest = $word;
                $shortest = 0;
                break;
            }
            if ($lev <= $shortest || $shortest < 0) {
                $closest = $word;
                $shortest = $lev;
            }
        }

        return $closest;
    }


    /**
     * escape编码
     * @param string $str 待编码字符串
     * @param string $charset 字符集
     * @return string
     */
    public static function escape($str='', $charset = 'utf-8') {
        preg_match_all("/[\x80-\xff].|[\x01-\x7f]+/", $str, $r);
        $ar = $r[0];
        foreach($ar as $k=>$v) {
            $ar[$k] = ord($v[0]) < 128 ? rawurlencode($v) : '%u'.bin2hex(iconv($charset, 'UCS-2', $v));
        }
        return join('', $ar);
    }


    /**
     * unescape解码
     * @param string $str 待解码字符串
     * @param string $charset 字符集
     * @return mixed|string
     */
    public static function unescape($str='', $charset = 'utf-8') {
        $str = rawurldecode($str);
        $str = preg_replace("/\%u([0-9A-Z]{4})/es", "iconv('UCS-2', '$charset', pack('H4', '$1'))", $str);
        return $str;
    }


    /**
     * 获取字串首字母
     * @param string $str
     * @return string
     */
    public static function getFirstLetter($str='') {
        $firstchar_ord = ord(strtoupper($str{0}));
        if($firstchar_ord >= 65 and $firstchar_ord <= 91) return strtoupper($str{0});
        if($firstchar_ord >= 48 and $firstchar_ord <= 57) return '#';
        $s = iconv("UTF-8", "gb2312", $str);
        $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
        if($asc>=-20319 and $asc<=-20284) return "A";
        if($asc>=-20283 and $asc<=-19776) return "B";
        if($asc>=-19775 and $asc<=-19219) return "C";
        if($asc>=-19218 and $asc<=-18711) return "D";
        if($asc>=-18710 and $asc<=-18527) return "E";
        if($asc>=-18526 and $asc<=-18240) return "F";
        if($asc>=-18239 and $asc<=-17923) return "G";
        if($asc>=-17922 and $asc<=-17418) return "H";
        if($asc>=-17417 and $asc<=-16475) return "J";
        if($asc>=-16474 and $asc<=-16213) return "K";
        if($asc>=-16212 and $asc<=-15641) return "L";
        if($asc>=-15640 and $asc<=-15166) return "M";
        if($asc>=-15165 and $asc<=-14923) return "N";
        if($asc>=-14922 and $asc<=-14915) return "O";
        if($asc>=-14914 and $asc<=-14631) return "P";
        if($asc>=-14630 and $asc<=-14150) return "Q";
        if($asc>=-14149 and $asc<=-14091) return "R";
        if($asc>=-14090 and $asc<=-13319) return "S";
        if($asc>=-13318 and $asc<=-12839) return "T";
        if($asc>=-12838 and $asc<=-12557) return "W";
        if($asc>=-12556 and $asc<=-11848) return "X";
        if($asc>=-11847 and $asc<=-11056) return "Y";
        if($asc>=-11055 and $asc<=-10247) return "Z";
        return '#';
    }


    /**
     * 匹配图片(提取img的地址)
     * @param string $content
     * @return array|bool
     */
    public static function matchImages($content = '') {
        preg_match_all ( '/<img.*src=(.*)[>|\\s]/iU', $content, $src);
        if (count ( $src [1] ) > 0) {
            $images = [];
            foreach ( $src [1] as $v ) {
                $images[] = trim ( $v, "\"'" ); //删除首尾的引号 ' "
            }
            return $images;
        } else {
            return false;
        }
    }



    /**
     * br标签转换为nl
     * @param string $string
     * @return mixed
     */
    public static function br2nl($string='') {
        return @preg_replace('/\<br(\s*)?\/?\>/i', "\n", $string);
    }


    /**
     * 移除字符串中的空格
     * @param string $str
     * @return mixed|string
     */
    public static function removeSpace($str=''){
        $str =  str_replace([(13), chr(10), "\n", "\r", "\t", '  '],['', '', '', '', '', ''], $str);
        $str = str_replace('&nbsp;','', $str);
        $str = preg_replace("/\s|　/i","",$str);
        return trim($str);
    }


    /**
     * 获取纯文本(不保留行内空格)
     * @param string $string
     * @return mixed|string
     */
    public static function getText($string='') {
        $string = strip_tags($string);

        //移除html,js,css标签
        $search = array (
            "'<script[^>]*?>.*?<\/script>'si", // 去掉 javascript
            "'<style[^>]*?>.*?<\/style>'si", // 去掉 css
            "'<[/!]*?[^<>]*?>'si", // 去掉 HTML 标记
            "'<!--[/!]*?[^<>]*?>'si", // 去掉 注释标记
            "'([rn])[s]+'", // 去掉空白字符
            "'&(quot|#34);'i", // 替换 HTML 实体
            "'&(amp|#38);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(nbsp|#160);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&#(d+);'"); // 作为PHP代码运行

        $replace = array (
            "",
            "",
            "",
            "",
            "\1",
            "\"",
            "&",
            "<",
            ">",
            " ",
            chr(161),
            chr(162),
            chr(163),
            chr(169),
            "chr(\1)");

        $string = preg_replace($search, $replace, $string);
        $string = self::removeSpace($string);

        return trim($string);
    }


    /**
     * 移除HTML标签(保留行内空格)
     * @param string $str
     * @return mixed|string
     */
    public static function removeHtml($str=''){
        $str=preg_replace( "@<(.*?)>@is", "", $str); //过滤标签
        $str=preg_replace("/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i", "", $str); //过滤img标签
        $str=preg_replace("@<style(.*?)<\/style>@is", "", $str); //过滤css
        $str=preg_replace("/\s+/", " ", $str); //过滤多余回车
        $str=preg_replace("/<[ ]+/si","<",$str); //过滤<__("<"号后面带空格)
        $str=preg_replace("/<\!--.*?-->/si","",$str); //注释
        $str=preg_replace("/<(\!.*?)>/si","",$str); //过滤DOCTYPE
        $str=preg_replace("/<(\/?html.*?)>/si","",$str); //过滤html标签
        $str=preg_replace("/<(\/?head.*?)>/si","",$str); //过滤head标签
        $str=preg_replace("/<(\/?meta.*?)>/si","",$str); //过滤meta标签
        $str=preg_replace("/<(\/?body.*?)>/si","",$str); //过滤body标签
        $str=preg_replace("/<(\/?link.*?)>/si","",$str); //过滤link标签
        $str=preg_replace("/<(\/?form.*?)>/si","",$str); //过滤form标签
        $str=preg_replace("/cookie/si","COOKIE",$str); //过滤COOKIE标签
        $str=preg_replace("/<(applet.*?)>(.*?)<(\/applet.*?)>/si","",$str); //过滤applet标签
        $str=preg_replace("/<(\/?applet.*?)>/si","",$str); //过滤applet标签
        $str=preg_replace("/<(style.*?)>(.*?)<(\/style.*?)>/si","",$str); //过滤style标签
        $str=preg_replace("/<(\/?style.*?)>/si","",$str); //过滤style标签
        $str=preg_replace("/<(title.*?)>(.*?)<(\/title.*?)>/si","",$str); //过滤title标签
        $str=preg_replace("/<(\/?title.*?)>/si","",$str); //过滤title标签
        $str=preg_replace("/<(object.*?)>(.*?)<(\/object.*?)>/si","",$str); //过滤object标签
        $str=preg_replace("/<(\/?objec.*?)>/si","",$str); //过滤object标签
        $str=preg_replace("/<(noframes.*?)>(.*?)<(\/noframes.*?)>/si","",$str); //过滤noframes标签
        $str=preg_replace("/<(\/?noframes.*?)>/si","",$str); //过滤noframes标签
        $str=preg_replace("/<(i?frame.*?)>(.*?)<(\/i?frame.*?)>/si","",$str); //过滤frame标签
        $str=preg_replace("/<(\/?i?frame.*?)>/si","",$str); //过滤frame标签
        $str=preg_replace("/<(script.*?)>(.*?)<(\/script.*?)>/si","",$str); //过滤script标签
        $str=preg_replace("/<(\/?script.*?)>/si","",$str); //过滤script标签
        $str=preg_replace("/javascript/si","Javascript",$str); //过滤script标签
        $str=preg_replace("/vbscript/si","Vbscript",$str); //过滤script标签
        $str=preg_replace("/on([a-z]+)\s*=/si","On\\1=",$str); //过滤script标签
        $str=preg_replace("/&#/si","&＃",$str); //过滤script标签

        return trim($str);
    }


    /**
     * 字符串/单词统计
     * @param string $str
     * @param int $type 0:按字符统计; 1:只统计英文单词; 2:按英文单词和中文字数
     * @return int|mixed
     */
    public static function stringWordCount($str='', $type=0){
        $str = trim($str);
        $len = 0;
        switch($type) {
            case 0: default:
            $len = mb_strlen(self::removeHtml(self::removeSpace($str)),'UTF-8');
            break;
            case 1:
                $len = str_word_count(self::removeHtml(html_entity_decode($str, ENT_QUOTES, 'UTF-8')));
                break;
            case 2:
                $str = self::removeHtml(html_entity_decode($str, ENT_QUOTES, 'UTF-8'));
                $utf8_cn = "/[\x{4e00}-\x{9fff}\x{f900}-\x{faff}]/u";//中文
                $utf8_symbol = "/[\x{ff00}-\x{ffef}\x{2000}-\x{206F}]/u";//中文标点符号

                $str = preg_replace($utf8_symbol, ' ', $str);
                $cnLen = preg_match_all($utf8_cn, $str, $textrr);

                $str = preg_replace($utf8_cn, ' ', $str);
                $enLen = str_word_count($str);

                $len = $cnLen + $enLen;
                break;
        }

        return $len;
    }


    /**
     * 格式化文件比特大小
     * @param int $size 文件大小(比特)
     * @param int $dec 小数位
     * @param string $delimiter 数字和单位间的分隔符
     * @return string
     */
    public static function formatBytes($size, $dec=2, $delimiter='') {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
        return round($size, $dec) . $delimiter . $units[$i];
    }


    /**
     * 隐藏邮箱中间部分为*
     * @param string $mail
     * @return mixed|string
     */
    public static function mailToxxx($mail=''){
        if(empty($mail)) return '';
        $n = strpos($mail,'@');
        if($n<3){
            $mail = substr_replace($mail,"****",$n,0);
        }elseif ($n<6){
            $mail = substr_replace($mail,"****",2,$n-2);
        }else {
            $mail = substr_replace($mail,"****",2,4);
        }

        return $mail;
    }


    /**
     * 隐藏手机/电话中间部分为*
     * @param string $phone
     * @return mixed|string
     */
    public static function phoneToxxx($phone=''){
        if(empty($phone)) return '';
        $IsWhat = preg_match('/(0[0-9]{2,3}[\-]?[2-9][0-9]{6,7}[\-]?[0-9]?)/i',$phone); //固定电话
        if($IsWhat == 1) {
            return preg_replace('/(0[0-9]{2,3}[\-]?[2-9])[0-9]{3,4}([0-9]{3}[\-]?[0-9]?)/i','$1****$2',$phone);

        } else {
            return  preg_replace('/(1[34578]{1}[0-9])[0-9]{4}([0-9]{4})/i','$1****$2',$phone);
        }
    }


    /**
     * 统计base64字符串大小(字节)
     * @param string $string base64字符串
     * @return int
     */
    public static function countBase64Byte($string='') {
        if(empty($string)) return 0;
        $string = preg_replace('/^(data:\s*(image|img)\/(\w+);base64,)/', '', $string);
        $string = str_replace('=','', $string);
        $len = strlen($string);
        $res = intval($len * (3/4));
        return $res;
    }






}