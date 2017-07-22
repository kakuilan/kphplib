<?php
/**
 * Copyright (c) 2017 LKK/lianq.net All rights reserved
 * User: kakuilan@163.com
 * Date: 2017/7/20
 * Time: 13:55
 * Desc: -lkk redis队列类
 */

namespace Lkk;

class LkkRedisQueueService extends LkkService {

    const REDIS_QUEUE_DATABASE = 9; //使用哪个库
    const REDIS_QUEUE_TYPE_ISSORT = 'issort'; //类型:有序队列(有序集合)
    const REDIS_QUEUE_TYPE_NOSORT = 'nosort'; //类型:无序队列(列表)
    const REDIS_QUEUE_SCORE_FIELD = 'queue_score'; //有序列表的分数字段名
    const REDIS_QUEUE_ALLKEY = 'all_key_table';
    const REDIS_QUEUE_TRANS_QUEU = 'transfer_que'; //中转队列key
    const REDIS_QUEUE_TRANS_TABL = 'transfer_tab'; //中转表key
    const REDIS_QUEUE_TRANS_TIME = 120; //默认的中转队列重新入栈时间,秒
    const REDIS_QUEUE_TRANS_LOCKKEY = 'trans_lock'; //中转队列的锁key
    const REDIS_QUEUE_TRANS_LOCKTIM = 3600; //中转队列的锁时间,秒

    private $redis;
    private $redisConf;
    private $timeout = 2.5;
    private static $prefix = 'que_';
    private static $allQuekeys = []; //当前类用过的所有队列key
    private $curQueName = ''; //当前队列key

    //外部传入的中转队列重新入栈时间
    public $transTime = 0;


    /**
     * 构造函数
     * RedisQueueService constructor.
     * @param array $vars
     */
    public function __construct(array $vars = []) {
        parent::__construct($vars);

        $redisConf = isset($vars['redisConf']) ? $vars['redisConf'] : [];
        $this->redis = self::getRedisClient($redisConf);
    }


    /**
     * 获取redis客户端连接对象
     * @param array $conf redis配置
     * @return \Redis
     */
    public static function getRedisClient($conf=[]) {
        static $redis;

        if(is_null($redis)) {
            if(empty($conf)) $conf = [
                'host' => '127.0.0.1',
                'port' => 6379,
                'password' => null,
            ];
            $redis = new \Redis();
            $redis->connect($conf['host'], $conf['port']);
            if(isset($conf['password']) && !empty($conf['password'])) {
                $redis->auth($conf['password']);
            }

            $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            $redis->select(self::REDIS_QUEUE_DATABASE);
        }

        return $redis;
    }


    /**
     * 获取hash表键
     * @return string
     */
    public static function getHashTableKey() {
        $key = self::$prefix . self::REDIS_QUEUE_ALLKEY;
        return $key;
    }


    /**
     * 获取中转队列key
     * @return string
     */
    public static function getTransQueueKey() {
        $key = self::$prefix . self::REDIS_QUEUE_TRANS_QUEU;
        return $key;
    }


    /**
     * 获取中转表key
     * @return string
     */
    public static function getTransTableKey() {
        $key = self::$prefix . self::REDIS_QUEUE_TRANS_TABL;
        return $key;
    }


    /**
     * 获取中转队列锁key
     * @param string $serverUniqueId 服务器唯一标识
     * @return string
     */
    public static function getTransLockKey($serverUniqueId='') {
        $key = self::$prefix . self::REDIS_QUEUE_TRANS_LOCKKEY."_{$serverUniqueId}";
        return $key;
    }


    /**
     * 新增队列名到哈希表
     * @param string $queueName 队列名
     * @param string $type 队列类型
     * @return mixed
     */
    private function addQueueName2Hash($queueName='', $type='') {
        if(empty($queueName)) {
            $this->setError('队列名称不能为空');
            return false;
        }

        if(empty($type)) $type = self::REDIS_QUEUE_TYPE_NOSORT;

        $allKey = self::getHashTableKey();
        $queKey = self::$prefix ."{$queueName}_{$type}";
        $res = $this->redis->hSetNx($allKey, $queueName, $queKey);
        if($res) {
            $res = $queKey;
        }else{
            $this->setError('该队列名已经存在');
        }

        return $res;
    }


    /**
     * 获取所有队列名
     * @return array
     */
    public function getAllQueueNames() {
        $allKey = self::getHashTableKey();
        $res = $this->redis->hGetAll($allKey);
        return empty($res) ? [] : $res;
    }


    /**
     * 统计所有队列
     * @return int
     */
    public function countAllQueueNames() {
        $allKey = self::getHashTableKey();
        $res = (int)$this->redis->hLen($allKey);
        return $res;
    }


    /**
     * 检查队列是否存在
     * @param string $queueName 队列名
     * @return bool
     */
    public function chkQueueExists($queueName='') {
        $res = false;
        if(empty($queueName)) return $res;

        $queueName = strtolower($queueName);
        if(!isset(self::$allQuekeys[$queueName])) {
            $allKey = self::getHashTableKey();
            $res = $this->redis->hExists($allKey, $queueName);
        }else{
            $res = true;
        }

        return (bool)$res;
    }


    /**
     * 获取队列基本信息
     * @param string $queueName
     * @return array|bool|object
     */
    public function getQueueBaseInfo($queueName='') {
        $res = false;
        if(empty($queueName)) return $res;

        $queueName = strtolower($queueName);
        if(!isset(self::$allQuekeys[$queueName])) {
            $allKey = self::getHashTableKey();
            $queueKey = $this->redis->hGet($allKey, $queueName);
        }else{
            $queueKey = self::$allQuekeys[$queueName];
        }

        if(!empty($queueKey)) {
            $res = [
                'queueName' => $queueName,
                'queueKey' => $queueKey,
                'isSort' => (stripos($queueKey, self::REDIS_QUEUE_TYPE_ISSORT)===false ? false : true),
            ];
            $res = (object)$res;
        }

        return $res;
    }


    /**
     * 新建队列
     * @param string $queueName 队列名(不区分大小写,建议小写)
     * @param bool $isSort 是否有序队列
     * @return bool|mixed
     */
    public function newQueue($queueName='', $isSort=false) {
        $res = false;
        if(empty($queueName)) {
            $this->setError('队列名称不能为空');
            return $res;
        }

        $queueName = strtolower($queueName);
        $type = $isSort ? self::REDIS_QUEUE_TYPE_ISSORT : self::REDIS_QUEUE_TYPE_NOSORT;

        $try = 0;
        while (!$res && $try<10) {
            $baseInfo = $this->getQueueBaseInfo($queueName);
            if(!empty($baseInfo)) {
                if($baseInfo->isSort != $isSort) {
                    $this->setError('该队列名已存在,且类型冲突');
                    break;
                }else{
                    self::$allQuekeys[$queueName] = $baseInfo->queueKey;
                    $this->curQueName = $queueName;
                    $res = true;
                }
            }else{
                $res = $this->addQueueName2Hash($queueName, $type);
                if($res) {
                    self::$allQuekeys[$queueName] = $res;
                    $this->curQueName = $queueName;
                }
            }

            $try++;
        }

        return $res;
    }


    /**
     * 删除队列(谨慎)
     * @param string $queueName
     * @return bool
     */
    public function delQueue($queueName='') {
        $res = false;
        if(empty($queueName)) {
            $this->setError('队列名不能为空');
            return $res;
        }

        $exists = $this->chkQueueExists($queueName);
        if(!$exists) {
            $this->setError('该队列名不存在');
            return $res;
        }

        $baseInfo = $this->getQueueBaseInfo($queueName);
        $allKey = self::getHashTableKey();
        $res = $this->redis->hDel($allKey, $baseInfo->queueKey);
        if($res) {
            unset(self::$allQuekeys[$queueName]);
            $this->redis->del($baseInfo->queueKey);
        }

        return (bool)$res;
    }


    /**
     * 重置当前操作的队列名
     * @param string $queueName
     * @return bool
     */
    public function resetCurrentQueue($queueName='') {
        $res = false;
        if(empty($queueName)) {
            $this->setError('队列名不能为空');
            return $res;
        }

        $exists = $this->chkQueueExists($queueName);
        if(!$exists) {
            $this->setError('该队列名不存在');
            return $res;
        }

        $this->curQueName = $queueName;
        if(!isset(self::$allQuekeys[$queueName])) self::$allQuekeys[$queueName] = $queueName;

        return true;
    }


    /**
     * 获取当前操作的队列名
     * @return string
     */
    public function getCurrentQueue() {
        return $this->curQueName;
    }


    /**
     * 获取队列长度
     * @param string $queueName 队列名
     * @return bool|int
     */
    public function len($queueName='') {
        $res = false;
        if(empty($queueName)) $queueName = $this->curQueName;

        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('队列名不存在');
            return $res;
        }

        if($baseInfo->isSort) {
            $res = (int)$this->redis->zCount($baseInfo->queueKey, PHP_INT_MIN, PHP_INT_MAX);
        }else{
            $res = (int)$this->redis->lLen($baseInfo->queueKey);
        }

        return $res;
    }


    /**
     * 队列头压入一个消息
     * @param mixed $item 消息
     * @param string $queueName 队列名
     * @return bool
     */
    public function add($item=[], $queueName='') {
        $res = false;
        if(empty($item)) {
            $this->setError('消息不能为空');
            return $res;
        }
        if(empty($queueName)) $queueName = $this->curQueName;

        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('队列名不存在');
            return $res;
        }

        if($baseInfo->isSort) {
            $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : time();
            $res = $this->redis->zAdd($baseInfo->queueKey, $score, $item);
        }else{
            $res = $this->redis->lPush($baseInfo->queueKey, $item);
        }

        return (bool)$res;
    }


    /**
     * 队列头压入多个消息
     * @param array $items 消息数组
     * @param string $queueName 队列名
     * @return bool
     */
    public function addMulti($items=[], $queueName='') {
        $res = false;
        if(empty($items)) {
            $this->setError('消息不能为空');
            return $res;
        }elseif (!is_array($items)) {
            $this->setError('数据非数组');
            return $res;
        }

        if(empty($queueName)) $queueName = $this->curQueName;
        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('队列名不存在');
            return $res;
        }

        $now = time();
        $this->redis->multi();
        foreach ($items as $k=>$item) {
            if($baseInfo->isSort) {
                $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : ($now+$k);
                $this->redis->zAdd($baseInfo->queueKey, $score, $item);
            }else{
                $this->redis->lPush($baseInfo->queueKey, $item);
            }
        }
        $mulRes = $this->redis->exec();
        if(isset($mulRes[0]) && !empty($mulRes[0])) {
            $res = true;
        }else{
            $this->setError('添加失败');
        }

        return $res;
    }


    /**
     * 队列尾压入一个消息
     * @param mixed $item 消息
     * @param string $queueName 队列名
     * @return bool
     */
    public function push($item=[], $queueName='') {
        $res = false;
        if(empty($item)) {
            $this->setError('消息不能为空');
            return $res;
        }
        if(empty($queueName)) $queueName = $this->curQueName;

        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('队列名不存在');
            return $res;
        }

        if($baseInfo->isSort) {
            $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : time();
            $res = $this->redis->zAdd($baseInfo->queueKey, $score, $item);
        }else{
            $res = $this->redis->rPush($baseInfo->queueKey, $item);
        }

        return (bool)$res;
    }


    /**
     * 队列尾压入多个消息
     * @param array $items 消息数组
     * @param string $queueName 队列名
     * @return bool
     */
    public function pushMulti($items=[], $queueName='') {
        $res = false;
        if(empty($items)) {
            $this->setError('消息不能为空');
            return $res;
        }elseif (!is_array($items)) {
            $this->setError('数据非数组');
            return $res;
        }

        if(empty($queueName)) $queueName = $this->curQueName;
        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('队列名不存在');
            return $res;
        }

        $now = time();
        $this->redis->multi();
        foreach ($items as $k=>$item) {
            if($baseInfo->isSort) {
                $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : ($now+$k);
                $this->redis->zAdd($baseInfo->queueKey, $score, $item);
            }else{
                $this->redis->rPush($baseInfo->queueKey, $item);
            }
        }
        $mulRes = $this->redis->exec();
        if(isset($mulRes[0]) && !empty($mulRes[0])) {
            $res = true;
        }else{
            $this->setError('添加失败');
        }

        return $res;
    }


    /**
     * 队列头移出元素
     * @param string $queueName 队列名
     * @return mixed
     */
    public function shift($queueName='') {
        $res = false;
        if(empty($queueName)) $queueName = $this->curQueName;

        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('队列名不存在');
            return $res;
        }

        $len = $this->len($queueName);
        if(empty($len)) {
            $this->setError('队列为空');
            return $res;
        }

        if($baseInfo->isSort) {
            $res = $this->redis->zRange($baseInfo->queueKey, 0, 0); //从小到大排
            if($res) $res = $res[0];
        }else{
            $res = $this->redis->lPop($baseInfo->queueKey);
        }

        $tranRes = false;
        if($res) $tranRes = $this->transfer($res, $queueName);
        $res = ($res && $tranRes) ? $res : false;

        return $res;
    }


    /**
     * 队列尾移出元素
     * @param string $queueName 队列名
     * @return bool
     */
    public function pop($queueName='') {
        $res = false;
        if(empty($queueName)) $queueName = $this->curQueName;

        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('队列名不存在');
            return $res;
        }

        $len = $this->len($queueName);
        if(empty($len)) {
            $this->setError('队列为空');
            return $res;
        }

        if($baseInfo->isSort) {
            $res = $this->redis->zRevRange($baseInfo->queueKey, 0, 0); //从大到小排
            if($res) $res = $res[0];
        }else{
            $res = $this->redis->rPop($baseInfo->queueKey);
        }

        $tranRes = false;
        if($res) $tranRes = $this->transfer($res, $queueName);
        $res = ($res && $tranRes) ? $res : false;

        return $res;
    }


    /**
     * 加入中转队列
     * @param mixed $item 消息
     * @param string $queueName 原队列名
     * @return mixed
     */
    public function transfer($item=[], $queueName='') {
        $res = false;
        if(empty($item)) {
            $this->setError('消息不能为空');
            return $res;
        }

        if(empty($queueName)) $queueName = $this->curQueName;
        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('原队列名不存在');
            return $res;
        }

        $score = time();
        $tranQueKey = self::getTransQueueKey();
        $tranTabKey = self::getTransTableKey();
        $tranItemData = [
            'queueName' => $queueName,
            'item' => $item,
        ];
        $tranItemKey = md5(serialize($tranItemData));

        //redis事务
        $this->redis->multi();
        $this->redis->zAdd($tranQueKey, $score, $tranItemKey);
        $this->redis->hSet($tranTabKey, $tranItemKey, $tranItemData);
        if($baseInfo->isSort) {
            $this->redis->zRem($baseInfo->queueKey, $item);
        }
        $tranRes = $this->redis->exec();
        if(!isset($tranRes[0]) || empty($tranRes[0])) {
            $this->setError('消息加入中转失败');
            $this->redis->hDel($tranTabKey, $tranItemKey);
            $this->redis->zRem($tranQueKey, $tranItemKey);
            //重新入栈
            if(!$baseInfo->isSort) $this->push($item, $queueName);
        }else{
            $res = true;
        }

        return $res;
    }


    /**
     * 消息确认(处理完毕后向队列确认,成功则从中转队列移除;失败则重新加入任务队列;若无确认,消息重新入栈)
     * @param mixed $item 消息
     * @param bool $procRes 处理结果:true成功,false失败
     * @param string $queueName 队列名,为空则取当前队列名
     * @return bool
     */
    public function confirm($item=[], $procRes=true, $queueName='') {
        $res = false;
        if(empty($item)) {
            $this->setError('消息不能为空');
            return $res;
        }

        if(empty($queueName)) $queueName = $this->curQueName;
        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('队列名不存在');
            return $res;
        }

        $tranQueKey = self::getTransQueueKey();
        $tranTabKey = self::getTransTableKey();
        $tranItemData = [
            'queueName' => $queueName,
            'item' => $item,
        ];
        $tranItemKey = md5(serialize($tranItemData));

        //redis事务
        $this->redis->multi();
        $this->redis->zRem($tranQueKey, $tranItemKey);
        $this->redis->hDel($tranTabKey, $tranItemKey);
        if(!$procRes) { //重新入栈
            if($baseInfo->isSort) {
                $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : time();
                $res = $this->redis->zAdd($baseInfo->queueKey, $score, $item);
            }else{
                $res = $this->redis->rPush($baseInfo->queueKey, $item);
            }
        }

        $cfmRes = $this->redis->exec();
        if(!isset($cfmRes[0]) || empty($cfmRes[0])) {
            $this->setError('消息确认失败');
        }else{
            $res = true;
        }

        return $res;
    }


    /**
     * 清空队列(谨慎)
     * @param string $queueName 队列名
     * @return bool
     */
    public function clear($queueName='') {
        $res = false;
        if(empty($queueName)) $queueName = $this->curQueName;

        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('队列名不存在');
            return $res;
        }

        $this->redis->del($baseInfo->queueKey);

        return true;
    }


    /**
     * 获取中转队列执行锁
     * @param string $serverUniqueId 服务器唯一标识
     * @return bool
     */
    public static function getTransQueueLock($serverUniqueId='') {
        $key = self::getTransLockKey($serverUniqueId);
        $now = time();
        $redis = self::getRedisClient();
        $res = $redis->setnx($key, $now);
        if($res) {
            $redis->expire($key, self::REDIS_QUEUE_TRANS_LOCKTIM);
        }

        return $res;
    }


    /**
     * 解锁中转队列执行
     * @param string $serverUniqueId 服务器唯一标识
     * @return int
     */
    public static function unlockTransQueue($serverUniqueId='') {
        $key = self::getTransLockKey($serverUniqueId);
        $redis = self::getRedisClient();
        $res = $redis->del($key);
        return $res;
    }


    /**
     * 获取单个消息的处理key
     * @param array $item
     * @param string $queueName
     * @return string
     */
    public static function getItemProcessKey($item=[], $queueName='') {
        $key = md5(json_encode($item));
        $key = self::$prefix . "_{$queueName}_{$key}";
        return $key;
    }


    /**
     * 获取单个消息的处理锁
     * @param array $item 消息
     * @param string $queueName 队列名
     * @param int $lockTime 锁时间,秒
     * @return bool
     */
    public static function getItemProcessLock($item=[], $queueName='', $lockTime=30) {
        $key = self::getItemProcessKey($item, $queueName);
        $now = time();
        $redis = self::getRedisClient();
        $res = $redis->setnx($key, $now);
        if($res) {
            $redis->expire($key, $lockTime);
        }

        return $res;
    }


    /**
     * 解锁单个消息的处理
     * @param array $item
     * @param string $queueName
     * @return int
     */
    public static function unlockItemProcess($item=[], $queueName='') {
        $key = self::getItemProcessKey($item, $queueName);
        $redis = self::getRedisClient();
        $res = $redis->del($key);
        return $res;
    }


    /**
     * 循环检查中转队列,重新入栈
     * @param string $serverUniqueId 服务器唯一标识
     * @return bool|int
     */
    public function loopTransQueue($serverUniqueId='') {
        $res = false;
        $lock = self::getTransQueueLock($serverUniqueId);
        if(!$lock) {
            $this->setError('已有其他进程正执行中转队列');
            return $res;
        }

        $tranQueKey = self::getTransQueueKey();
        $tranTabKey = self::getTransTableKey();
        $len = (int)$this->redis->hLen($tranTabKey);
        if($len<=0) {
            $this->setError('中转队列为空');
            self::unlockTransQueue($serverUniqueId);
            return $res;
        }

        $beginTime = time();
        $expire = ($this->transTime>0) ? intval($this->transTime) : self::REDIS_QUEUE_TRANS_TIME;
        set_time_limit(self::REDIS_QUEUE_TRANS_LOCKTIM);
        $successNum = 0;

        try{
            while ($list = $this->redis->zRange($tranQueKey, 0, 0, true)) {
                $itemKey = null;
                $time = 0;
                foreach ($list as $itemKey=>$time) {
                    break;
                }

                $now = time();
                if(($now-$beginTime)> self::REDIS_QUEUE_TRANS_LOCKTIM) {
                    $this->setError('中转队列处理超时');
                    break;
                }

                $tranItem = $this->redis->hGet($tranTabKey, $itemKey);
                if(empty($tranItem)) {
                    $this->redis->zRem($tranQueKey, $itemKey);
                    $this->setError('消息不存在');
                    continue;
                }

                if(($now - $time) > $expire) {
                    //检查原队列是否存在
                    $oldQueInfo = $this->getQueueBaseInfo($tranItem['queueName']);
                    if($oldQueInfo) { //超时的重新入栈
                        $this->redis->multi();
                        $this->redis->hDel($tranTabKey, $itemKey);
                        $this->redis->zRem($tranQueKey, $itemKey);
                        if($oldQueInfo->isSort) {
                            $score = isset($tranItem['item'][self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$tranItem['item'][self::REDIS_QUEUE_SCORE_FIELD] : time();
                            $this->redis->zAdd($oldQueInfo->queueKey, $score, $tranItem['item']);
                        }else{
                            $this->redis->rPush($oldQueInfo->queueKey, $tranItem['item']);
                        }
                        $addRes = $this->redis->exec();
                        if(!isset($addRes[0]) || empty($addRes[0])) {
                            $this->setError('消息重新入栈失败');
                        }else{
                            $successNum++;
                        }
                    }else{ //丢弃
                        $this->setError('消息原队列不存在');
                        $this->redis->hDel($tranTabKey, $itemKey);
                        $this->redis->zRem($tranQueKey, $itemKey);
                    }
                }else{
                    $this->setError('消息未过期');
                    $res = true;
                    break;
                }

            }
        }catch (\Exception $e) {
            $msg = $e->getMessage();
            $this->setError('发生意外,'.$msg);
        }
        self::unlockTransQueue($serverUniqueId);

        return (int)$successNum;
    }


}