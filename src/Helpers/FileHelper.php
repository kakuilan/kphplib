<?php
/**
 * Copyright (c) 2016 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2016/12/25
 * Time: 17:02
 * Desc: -文件助手类
 */


namespace Lkk\Helpers;

class FileHelper {


    /**
     * 编译php文件
     * @param string $filename 文件名
     * @return string
     */
    public static function compile($filename) {
        $content    =   php_strip_whitespace($filename); //无法处理heredoc <<<EOF
        $content    =   trim(substr($content, 5));
        if(0===strpos($content,'namespace')){
            $content    =   preg_replace('/namespace\s(.*?);/','namespace \\1{',$content,1);
        }else{
            $content    =   'namespace {'.$content;
        }
        if ('?>' == substr($content, -2))
            $content    = substr($content, 0, -2);
        return $content.'}';
    }


    /**
     * 压缩PHP文件 (去除代码中的空白和注释)
     * @param string $src 文件路径
     * @return bool|string
     */
    public static function compressPhp($src) {
        // Whitespaces left and right from this signs can be ignored
        static $IW = array(
            T_CONCAT_EQUAL,             // .=
            T_DOUBLE_ARROW,             // =>
            T_BOOLEAN_AND,              // &&
            T_BOOLEAN_OR,               // ||
            T_IS_EQUAL,                 // ==
            T_IS_NOT_EQUAL,             // != or <>
            T_IS_SMALLER_OR_EQUAL,      // <=
            T_IS_GREATER_OR_EQUAL,      // >=
            T_INC,                      // ++
            T_DEC,                      // --
            T_PLUS_EQUAL,               // +=
            T_MINUS_EQUAL,              // -=
            T_MUL_EQUAL,                // *=
            T_DIV_EQUAL,                // /=
            T_IS_IDENTICAL,             // ===
            T_IS_NOT_IDENTICAL,         // !==
            T_DOUBLE_COLON,             // ::
            T_PAAMAYIM_NEKUDOTAYIM,     // ::
            T_OBJECT_OPERATOR,          // ->
            T_DOLLAR_OPEN_CURLY_BRACES, // ${
            T_AND_EQUAL,                // &=
            T_MOD_EQUAL,                // %=
            T_XOR_EQUAL,                // ^=
            T_OR_EQUAL,                 // |=
            T_SL,                       // <<
            T_SR,                       // >>
            T_SL_EQUAL,                 // <<=
            T_SR_EQUAL,                 // >>=
        );
        if(is_file($src)) {
            if(!$src = file_get_contents($src)) {
                return false;
            }
        }
        $tokens = token_get_all($src);

        $new = "";
        $c = sizeof($tokens);
        $iw = false; // ignore whitespace
        $ih = false; // in HEREDOC
        $ls = "";    // last sign
        $ot = null;  // open tag
        for($i = 0; $i < $c; $i++) {
            $token = $tokens[$i];
            if(is_array($token)) {
                list($tn, $ts) = $token; // tokens: number, string, line
                $tname = token_name($tn);
                if($tn == T_INLINE_HTML) {
                    $new .= $ts;
                    $iw = false;
                } else {
                    if($tn == T_OPEN_TAG) {
                        if(strpos($ts, " ") || strpos($ts, "\n") || strpos($ts, "\t") || strpos($ts, "\r")) {
                            $ts = rtrim($ts);
                        }
                        $ts .= " ";
                        $new .= $ts;
                        $ot = T_OPEN_TAG;
                        $iw = true;
                    } elseif($tn == T_OPEN_TAG_WITH_ECHO) {
                        $new .= $ts;
                        $ot = T_OPEN_TAG_WITH_ECHO;
                        $iw = true;
                    } elseif($tn == T_CLOSE_TAG) {
                        if($ot == T_OPEN_TAG_WITH_ECHO) {
                            $new = rtrim($new, "; ");
                        } else {
                            $ts = " ".$ts;
                        }
                        $new .= $ts;
                        $ot = null;
                        $iw = false;
                    } elseif(in_array($tn, $IW)) {
                        $new .= $ts;
                        $iw = true;
                    } elseif($tn == T_CONSTANT_ENCAPSED_STRING
                        || $tn == T_ENCAPSED_AND_WHITESPACE)
                    {
                        if($ts[0] == '"') {
                            $ts = addcslashes($ts, "\n\t\r");
                        }
                        $new .= $ts;
                        $iw = true;
                    } elseif($tn == T_WHITESPACE) {
                        $nt = @$tokens[$i+1];
                        if(!$iw && (!is_string($nt) || $nt == '$') && !in_array($nt[0], $IW)) {
                            $new .= " ";
                        }
                        $iw = false;
                    } elseif($tn == T_START_HEREDOC) {
                        $new .= "<<<S\n";
                        $iw = false;
                        $ih = true; // in HEREDOC
                    } elseif($tn == T_END_HEREDOC) {
                        $new .= "S;";
                        $iw = true;
                        $ih = false; // in HEREDOC
                        for($j = $i+1; $j < $c; $j++) {
                            if(is_string($tokens[$j]) && $tokens[$j] == ";") {
                                $i = $j;
                                break;
                            } else if($tokens[$j][0] == T_CLOSE_TAG) {
                                break;
                            }
                        }
                    } elseif($tn == T_COMMENT || $tn == T_DOC_COMMENT) {
                        $iw = true;
                    } else {
                        if(!$ih) {
                            $ts = strtolower($ts);
                        }
                        $new .= $ts;
                        $iw = false;
                    }
                }
                $ls = "";
            } else {
                if(($token != ";" && $token != ":") || $ls != $token) {
                    $new .= $token;
                    $ls = $token;
                }
                $iw = true;
            }
        }
        return $new;
    }


    /**
     * 写文件
     * @param string $file 文件路径
     * @param string $data 内容
     * @param bool $append 是否追加
     * @param int $mode 文件模式
     * @return bool|int
     */
    public static function writeFile($file, $data, $append=false, $mode=0766) {
        $result = false;
        $dir = dirname($file);
        if (mkdir($dir, 0766, true)) {
            if ($fp = @fopen($file, $append ? 'ab' : 'wb')) {
                $result = @fwrite($fp, $data);
                @fclose($fp);
                @chmod($file, $mode);
            }
        }

        return $result;
    }


    /**
     * 移除 UTF8的BOM头
     * @param $string
     * @return string
     */
    public static function removeBom($string) {
        if(substr($string, 0, 3) == pack('CCC', 239, 187, 191)) return substr($string, 3);
        return $string;
    }


    /**
     * 创建ZIP压缩包
     * @param array $files 要压缩的文件数组
     * @param string $destination 目标文件
     * @param bool $overwrite 是否覆盖
     * @return bool
     */
    public static function createZip($files = array(),$destination = '',$overwrite = false) {
        //if the zip file already exists and overwrite is false, return false
        if(file_exists($destination) && !$overwrite) { return false; }
        //vars
        $valid_files = array();
        //if files were passed in...
        if(is_array($files)) {
            //cycle through each file
            foreach($files as $file) {
                //make sure the file exists
                if(file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        //if we have good files...
        if(count($valid_files)) {
            //create the archive
            $zip = new \ZipArchive();
            if($zip->open($destination,$overwrite ? \ZIPARCHIVE::OVERWRITE : \ZIPARCHIVE::CREATE) !== true) {
                return false;
            }

            //add the files
            $desPath = dirname($destination);
            foreach($valid_files as $file) {
                $localname = str_replace($desPath, '', $file);
                $zip->addFile($file,$localname);
            }
            //debug
            //echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

            //close the zip -- done!
            $zip->close();

            //check to make sure the file exists
            return file_exists($destination);
        } else {
            return false;
        }
    }


    /**
     * 下载文件 (效率慢,只适应后台管理)
     * @param string $file 文件路径
     * @param int $limit 大小限制K
     */
    public static function downFile($file='', $limit=100){
        if(empty($file) || !file_exists($file) || !is_file($file)) {
            die('File does not exist!');
        }

        $info = pathinfo($file);
        $fileName = $info['basename'];
        $size = filesize($file);

        if($size > $limit * 1024 ) {
            die("File larger than {$limit}K");
        }

        //中文名处理
        $ua = $_SERVER["HTTP_USER_AGENT"];
        $encoded_filename = urlencode($fileName);
        $encoded_filename = str_replace("+", "%20", $encoded_filename);

        ob_end_clean();
        header("Content-Encoding: none");
        header("Content-Type: ".(strpos($ua, 'MSIE') ? 'application/octetstream' : 'application/octet-stream'));
        if (preg_match("/MSIE/", $ua)) {
            header('Content-Disposition: attachment; filename="' . $encoded_filename . '"');
        } else if (preg_match("/Firefox/", $ua)) {
            header('Content-Disposition: attachment; filename*="utf8\'\'' . $fileName . '"');
        } else {
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
        }

        header("Content-Transfer-Encoding: Binary");
        header("Content-length: ".$size);
        header("Pragma: no-cache");
        header("Expires: 0");

        readfile($file);
        $tmp=ob_get_contents();
        ob_end_clean();
        exit();
    }


    /**
     * 获取文件扩展名
     * @param string $filename 文件路径
     * @return mixed
     */
    public static function getFileExt($filename) {
        if(strpos($filename, '?')) {
            $filename = substr($filename, 0, strpos($filename, '?'));
        }
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }


    /**
     * 将图片文件转换为base64编码
     * @param string $img_file 图片绝对路径
     * @return string
     */
    public static function img2Base64($img_file) {
        $img_base64 = '';
        $img_info = getimagesize($img_file);            //取得图片的大小，类型等
        $fp = fopen($img_file,"r");                     //图片是否可读权限
        if($fp){
            $file_content = chunk_split(base64_encode(fread($fp,filesize($img_file))));//base64编码
            $img_type = 'jpg';
            switch($img_info[2]){           //判读图片类型
                case 1:
                    $img_type="gif";
                    break;
                case 2:
                    $img_type="jpg";
                    break;
                case 3:
                    $img_type="png";
                    break;
            }
            $img_base64 = 'data:image/'.$img_type.';base64,'.$file_content;//合成图片的base64编码
            fclose($fp);
        }

        return $img_base64;         //返回图片的base64
    }


}