<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/7/14
 * Time: 16:23
 * Desc: -lkk http异步请求类
 */

namespace Lkk;

class LkkHttp {

    public		$param		= [];	    //构造函数参数
    public		$url		= '';		//传入的完整请求url,包括"http://"
    public 		$get		= [];	    //传入的get数组,须是键值对
    public		$post		= [];	    //传入的post数组,须是键值对
    public		$cookie		= [];	    //传入的cookie数组,须是键值对
    public		$timeOut	= 30;		//请求超时秒数
    public		$timeLimit	= 0;		//脚本执行时间限制
    public		$result		= '';		//获取到的数据
    private		$gzip		= true;		//是否开启gzip压缩
    private		$fop		= null;		//fsockopen资源句柄
    private		$host		= '';		//主机
    private		$port		= '';		//端口
    private		$referer	= '';		//伪造来路
    private		$requestUri	= '';		//实际请求uri
    private		$header		= '';		//头信息
    private		$agent		= '';		//模拟客户端信息
    private		$block		= 1;		//网络流状态.1为阻塞,0为非阻塞
    private		$limit		= 128;		//读取的最大字节数
    private		$redirect	= 0;		//重定向次数
    private		$contType	= '';		//HTTP头:Content Type


    /**
     * 构造函数
     * @param array $param
     * timeOut 请求超时秒数
     * timeLimit 脚本执行时间限制
     * randomAgent 0源客户端,1随机,其他为自定义
     *
     */
    public function __construct($param=['timeOut'=>30, 'timeLimit'=>0, 'randomAgent'=>1]) {
        ignore_user_abort(true);//如果客户端断开连接,不会引起脚本abort

        if(!isset($param['timeOut'])) $param['timeOut'] = 30;
        if(!isset($param['timeLimit'])) $param['timeOut'] = 0;
        if(!isset($param['randomAgent'])) $param['timeOut'] = 1;

        if($param['timeOut'] >0) {
            $this->timeOut = $param['timeOut'];
        }

        if($param['timeLimit'] >0) {
            set_time_limit($param['timeLimit']);
        }else{
            set_time_limit(0);//取消脚本执行延时上限
        }

        $this->param = $param;
    }


    /**
     * 根据数组创建cookie数据
     * @param array $data cookie数组(键值对)
     * @return string
     */
    private static function _buildCookie(array $data){
        $res = '';
        foreach($data as $k=>$v){
            $res .= "{$k}=".urlencode(strval($v))."; ";
        }
        return $res;
    }


    /**
     * 根据数组创建query
     * @param array $data 数据数组(键值对,最多二维)
     * @return string
     */
    private static function _buidQuery(array $data){
        $res = [];
        foreach($data as $k=>$v){
            if(is_array($v)){
                foreach($v as $k2=>$v2){
                    $res[] = "{$k2}[]=" .strval($v2);
                }
            }else{
                $res[] = "{$k}=" .strval($v);
            }
        }
        return implode("&", $res);
    }


    /**
     * 设置referer
     * @param $referer
     */
    public function setReferer($referer){
        $this->referer = $referer;
    }


    /**
     * 设置ContentType
     * @param $type
     */
    public function setContentType($type){
        $this->contType = $type;
    }


    /**
     * 设置agent
     * @param string $agent
     */
    public function setAgent($agent='') {
        if(!empty($agent)) {
            $this->agent = $this->param['randomAgent'] = $agent;
        }else{
            $agentArr = [
                'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:50.0) Gecko/20100101 Firefox/50.0',
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36',
                'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Win64; x64; Trident/5.0)',
            ];

            if($this->param['randomAgent']==1) {
                $ind = rand(0,3);
                $this->agent = $agentArr[$ind];
            }elseif($this->param['randomAgent']==0 && isset($_SERVER['HTTP_USER_AGENT'])) {
                $this->agent = $_SERVER['HTTP_USER_AGENT'];
            }elseif($this->param['randomAgent']!=''){
                $this->agent = $this->param['randomAgent'];
            }else{
                $this->agent = $agentArr[0];
            }
        }
    }


    /**
     * 解析URL并创建资源句柄
     * @return bool
     */
    private function _analyzeUrl(){
        if(empty($this->url)) return false;
        $urlArray = parse_url($this->url);
        $isHttps  = (bool)(strtolower($urlArray['scheme']) == 'https');
        if(!isset($urlArray['scheme']) || !isset($urlArray['host'])){
            return false;//URL非法
        }
        !isset($urlArray['host'])  && $urlArray['host'] = '';
        !isset($urlArray['path'])  && $urlArray['path'] = '';
        !isset($urlArray['query']) && $urlArray['query'] = '';
        !isset($urlArray['port'])  && $urlArray['port'] = 80;
        //get参数
        if(!empty($this->get)){
            $getQuery = self::_buidQuery($this->get);
            $urlArray['query'] = empty($urlArray['query']) ? $getQuery : $urlArray['query'] .'&'.$getQuery;
        }
        $this->host			= $urlArray['host'];
        $this->port			= $urlArray['port'];
        $this->referer		= empty($this->referer) ? $urlArray['scheme'] .'://'.$this->host .'/' : $this->referer;
        $this->requestUri	= $urlArray['path'] ? $urlArray['path'].($urlArray['query'] ? '?'.$urlArray['query'] : '') : '/';
        $this->fop = $isHttps ?
            fsockopen('ssl://'. $urlArray['host'], 443, $errno, $errstr, $this->timeOut) :
            fsockopen($this->host, $this->port, $errno, $errstr, $this->timeOut);
        if(!$this->fop){
            $this->result	= "$errstr ($errno)<br />\n";
            return false;
        }

        $this->setAgent();
        return true;
    }


    /**
     * 拼装HTTP的header
     */
    private function _assemblyHeader(){
        $method = empty($this->post) ? 'GET' : 'POST';
        $gzip = $this->gzip ? 'gzip, ' : '';

        //cookie数据
        if(is_array($this->cookie)){
            if(function_exists('http_build_cookie')){//需安装pecl_http
                $this->cookie = http_build_cookie($this->cookie);
            }else $this->cookie = self::_buildCookie($this->cookie);
        }else{
            $this->cookie = strval($this->cookie);
        }

        //post数据
        if(is_array($this->post)){
            if(function_exists('http_build_query')){
                $this->post = http_build_query($this->post);
            }else $this->post = self::_buidQuery($this->post);
        }else $this->post = strval($this->post);
        $header	= "$method $this->requestUri HTTP/1.0\r\n";
        $header	.= "Accept: */*\r\n";
        $header	.= "Referer: $this->referer\r\n";
        $header	.= "Accept-Language: zh-cn\r\n";
        if(!empty($this->contType)){
            $header	.= "Content-Type: $this->contType\r\n";
        }elseif(!empty($this->post)){
            $header	.= "Content-Type: application/x-www-form-urlencoded\r\n";
        }
        $header	.= "User-Agent: ".$this->agent."\r\n";
        $header	.= "Host: $this->host\r\n";
        if(!empty($this->post)){
            $header	.= 'Content-Length: '.strlen($this->post)."\r\n";
        }
        $header	.= "Connection: Close\r\n";
        $header	.= "Accept-Encoding: {$gzip}deflate\r\n";
        $header	.= "Cookie: $this->cookie\r\n\r\n";
        $header	.= $this->post;
        $this->header	= $header;
    }


    /**
     * 返回状态检查,301、302重定向处理
     * @param string $header 头信息
     * @return bool|int|string
     */
    private function _checkReceiveHeader($header){
        if(strstr($header,' 301 ') || strstr($header,' 302 ')){//重定向处理
            preg_match("/Location:(.*?)$/im", $header, $match);
            $url = (empty($match)) ? '' : trim($match[1]);
            preg_match("/Set-Cookie:(.*?)$/im",$header,$match);
            $cookie	= (empty($match)) ? '' : $match[1];
            if($this->redirect <3){
                $this->redirect++;
                $this->result	= $this->get($url,'',$this->post,$cookie);
            }
            return $this->result;
        }elseif(!strstr($header,' 200 ')){//找不到域名或网址
            return false;
        }else{
            return 200;
        }
    }


    /**
     * gzip解压
     * @param string $data 数据
     * @return string
     */
    private static function _gzdecode($data){
        $flags = ord(substr($data, 3, 1));
        $headerlen = 10;
        $extralen = 0;
        $filenamelen = 0;
        if ($flags & 4) {
            $extralen = unpack('v' ,substr($data, 10, 2));
            $extralen = $extralen[1];
            $headerlen += 2 + $extralen;
        }
        if ($flags & 8) $headerlen = strpos($data, chr(0), $headerlen) + 1;
        if ($flags & 16) $headerlen = strpos($data, chr(0), $headerlen) + 1;
        if ($flags & 2) $headerlen += 2;
        $unpacked = @gzinflate(substr($data, $headerlen));
        if ($unpacked === false) $unpacked = $data;
        return $unpacked;
    }


    /**
     * 发送http异步请求(只请求,不返回,支持对目标服务器的apache/nginx异步并发处理)
     * @param string $url 完整请求地址
     * @param array $get get数据
     * @param array $post post数据
     * @param array $cookie cookie数据
     * @param int $timeOut 超时(毫秒)
     * @return bool|int
     */
    public function request($url, $get=[], $post=[], $cookie=[], $timeOut=0){
        $this->url		= $url;
        $this->get		= $get;
        $this->post		= $post;
        $this->cookie	= $cookie;
        if(!$this->_analyzeUrl()) return false;
        $this->_assemblyHeader();
        stream_set_blocking($this->fop, 0);//非阻塞,无须等待
        stream_set_timeout($this->fop, 60);

        fwrite($this->fop, $this->header);
        $status = stream_get_meta_data($this->fop);
        $res = false;
        if(!$status['timed_out']){
            //读取返回头信息
            $startTime = microtime(true);
            $h='';
            $useTime = 0;
            $timeOut = ($timeOut>0) ? $timeOut * 1000 : 5000;
            while(!feof($this->fop) && $useTime<= $timeOut){
                if(($header = @fgets($this->fop)) && ($header == "\r\n" ||  $header == "\n") ){
                    break;
                }

                $h .= $header;
                if(strlen($h) >0){
                    $res = true;
                    break;
                }

                $useTime = (microtime(true) - $startTime) * pow(10,4);
                if($useTime > $timeOut) break;

                usleep(10);
            }
        }

        @fclose($this->fop);
        unset($this->fop);
        return $res;
    }


    /**
     * 请求并获取内容
     * @param string $url 完整请求地址
     * @param array $get get数据
     * @param array $post post数据
     * @param array $cookie cookie数据
     * @param int $timeOut 超时(秒)
     * @param int $getLength 要获取的内容长度(字节)
     * @return bool|string
     */
    public function get($url, $get=[], $post=[], $cookie=[], $timeOut=30, $getLength=0){
        $this->url		= $url;
        $this->get		= $get;
        $this->post		= $post;
        $this->cookie	= $cookie;
        $this->timeOut	= $timeOut;
        $getLength		= intval($getLength);
        $totalLength	= 0;//已经获取的长度
        if(!$this->_analyzeUrl()) return false;
        $this->_assemblyHeader();
        stream_set_blocking($this->fop, $this->block);
        stream_set_timeout($this->fop, $this->timeOut);
        fwrite($this->fop, $this->header);

        //读取返回头信息
        $status = stream_get_meta_data($this->fop);
        $h='';
        while(!feof($this->fop) && !$status['timed_out']){
            if(($header = @fgets($this->fop)) && ($header == "\r\n" ||  $header == "\n")){
                break;
            }
            $h .= $header;
            $status = stream_get_meta_data($this->fop);
        }
        $checkHttp	= $this->_checkReceiveHeader($h);
        if($checkHttp!=200) return $checkHttp;

        //读内容
        $return = '';
        $this->gzip = false;
        if(strstr($h,'gzip')) $this->gzip = true;
        $readLen = ($this->limit == 0 || $this->limit > 128) ? 128 : $this->limit;//每次读取的长度
        while(!feof($this->fop) && !$status['timed_out']){
            if($getLength>0 && $getLength <= $totalLength){//读取总字节长度限制
                break;
            }

            $data = fread($this->fop, $readLen);
            if($data == ''){//有些服务器不行,须自行判断FOEF
                break;
            }
            $totalLength += strlen($data);
            $return	.= $data;
            $status = stream_get_meta_data($this->fop);
        }
        if($status['timed_out']) return false;
        @fclose($this->fop);
        $this->result	= $this->gzip ? self::_gzdecode($return) : $return;
        if($getLength>0) $this->result = substr($this->result, 0, $getLength);

        return $this->result;
    }


    /**
     * 获取HTTP状态码
     * @param string $url 完整请求地址
     * @param int $timeOut 超时
     * @return bool
     */
    public function getHttpStatusCode($url,$timeOut=60){
        $this->url		= $url;
        $this->timeOut	= $timeOut;
        ini_set('default_socket_timeout', $this->timeOut);//socket流的超时时间
        if(!$this->_analyzeUrl()){
            return false;
        }
        $header_arr = get_headers($this->url);
        list($version,$status_code,$msg) = explode(' ',$header_arr[0], 3);
        $this->result = [$version,$status_code,$msg];
        return $status_code;
    }


    /**
     * 发送文件二进制流
     * @param string $url
     * @param string $filename 文件路径
     * @param array $get
     * @param array $cookie
     * @param int $timeOut
     * @param int $getLength
     * @return bool|string
     */
    public function sendFileStream($url, $filename, $get=[], $cookie=[], $timeOut=30, $getLength=0) {
        $res = false;
        $handle = @fopen($filename, "rb");
        if($handle) {
            $contents = fread($handle, filesize($filename));
            fclose($handle);
            $this->setContentType('application/octet-stream');
            $res = $this->get($url, $get, $contents, $cookie, $timeOut, $getLength);
            $this->setContentType('');
        }
        return $res;
    }


    /**
     * 解析文件路径
     * @param string $filepath 文件路径
     * @return mixed
     */
    private static function _pathinfo($filepath) {
        $res = '';
        preg_match('%^(.*?)[\\\\/]*(([^/\\\\]*?)(\.([^\.\\\\/]+?)|))[\\\\/\.]*$%im',$filepath,$m);
        if($m[1]) $res['dirname']=$m[1];
        if($m[2]) $res['basename']=$m[2];
        if($m[5]) $res['extension']=$m[5];
        if($m[3]) $res['filename']=$m[3];
        return $res;
    }


    /**
     * 获取并保存远程文件
     * @param string $url 完整请求地址
     * @param string $savePath 保存路径
     * @param string $saveName 保存文件名
     * @param array $get get数据
     * @param array $post post数据
     * @param array $cookie cookie数据
     * @return bool
     */
    public function save($url, $savePath, $saveName='', $get=[], $post=[], $cookie=[], $timeOut=30){
        $data = $this->get($url, $get, $post, $cookie, $timeOut);
        if(empty($data)) return false;
        //自动文件名
        if(empty($saveName)){
            $urlFile = end(explode('/',$url));
            if(strpos($urlFile,'.') ==false){
                //文件类型
                $bin = substr($data,0,2);
                $strInfo = @unpack("C2chars", $bin);
                $typeCode = intval($strInfo['chars1'].$strInfo['chars2']);
                $fileType = '';
                switch ($typeCode) {
                    case 4742	: $fileType = 'css'; break;
                    case 6033   : $fileType = 'html'; break;
                    case 6677   : $fileType = 'bmp'; break;
                    case 7173   : $fileType = 'gif'; break;
                    case 7784   : $fileType = 'midi'; break;
                    case 7790   : $fileType = 'exe'; break;
                    case 8297   : $fileType = 'rar'; break;
                    case 12334	: $fileType = 'json'; break;
                    case 13780  : $fileType = 'png'; break;
                    case 40102  : $fileType = 'js'; break;
                    case 255216 : $fileType = 'jpg'; break;
                    default     : $fileType = 'unknown';break;
                }
                $saveName = date('YmdHis'). substr(md5($url), 8,4) .'.' .$fileType;
            }else{
                $pathinfo = self::_pathinfo($url);
                $saveName = $pathinfo['basename'];
            }
        }
        $fullSavePath = $savePath . DIRECTORY_SEPARATOR .$saveName;
        if(file_exists($fullSavePath)){
            return false;
        }else{
            $res = file_put_contents($fullSavePath, $data);
            return (bool)$res;
        }
    }


}