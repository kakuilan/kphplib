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

    const REDIS_QUEUE_DATABASE      = 9; //使用哪个库
    const REDIS_QUEUE_TYPE_ISSORT   = 'issort'; //类型:有序队列(有序集合)
    const REDIS_QUEUE_TYPE_NOSORT   = 'nosort'; //类型:无序队列(列表),先进先出
    const REDIS_QUEUE_SCORE_FIELD   = 'queue_score'; //有序列表的分数字段名
    const REDIS_QUEUE_ALLKEY        = 'all_key_table';
    const REDIS_QUEUE_TRANS_QUEU    = 'transfer_que'; //中转队列key
    const REDIS_QUEUE_TRANS_TABL    = 'transfer_tab'; //中转表key
    const REDIS_QUEUE_TRANS_TIME    = 120; //默认的中转队列重新入栈时间,秒
    const REDIS_QUEUE_TRANS_LOCKKEY = 'trans_lock'; //中转队列的锁key
    const REDIS_QUEUE_TRANS_LOCKTIM = 3600; //中转队列的锁时间,秒

    public $redis; //redis客户端对象
    public $redisConf; //redis配置
    private static $timeout = 2.5;
    private static $prefix = 'que_';
    private static $persistent_id = 'redis_queue_conn';
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

        $this->redisConf = isset($vars['redisConf']) ? $vars['redisConf'] : [];
        //$this->redis = self::getRedisClient($this->redisConf);
    }


    /**
     * 析构函数
     */
    public function __destruct() {
        parent::__destruct();
        //$this->redis->close();
    }


    /**
     * 重置redis长连接ID
     * @param string $id
     */
    public static function resetPersistentId($id='') {
        if(!empty($id)) self::$persistent_id = $id;
    }


    /**
     * 获取redis长连接ID
     * @return string
     */
    public static function getPersistentId() {
        return self::$persistent_id;
    }


    /**
     * 获取redis客户端连接对象
     * @param array $conf redis配置
     * @return \Redis
     */
    public static function getRedisClient($conf=[]) {
        static $redisArr;
        if(empty($conf)) $conf = [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'select' => null, //哪个库
            'wait_timeout' => 120, //保持连接超时,秒
        ];
        $key = md5(json_encode($conf));

        $connInfo = is_null($redisArr) ? [] : ($redisArr[$key] ?? []);
        $now = time();
        $socketTimeout = ini_get('default_socket_timeout');
        $waitTimeout = intval($conf['wait_timeout'] ?? 120);
        if($socketTimeout>0) $waitTimeout = min($socketTimeout, $waitTimeout);
        $lastTime = $connInfo['first_connect_time'] ?? 0;
        $maxTime = $lastTime + $waitTimeout;

        $pingRes = false;
        if($connInfo) {
            $pingRes = true;
            if(!($now>=$lastTime && $now<$maxTime) ) {
                try {
                    $ping = $connInfo['redis']->ping();
                    $pingRes = (strpos($ping, "PONG") !== false);
                }catch (\Throwable $e) {
                    $pingRes = false;
                }
            }
        }

        if(empty($connInfo) || !$pingRes) {
            $redis = new \Redis();
            $persistentId = self::getPersistentId();
            $redis->pconnect($conf['host'], $conf['port'], 0, $persistentId);
            if(isset($conf['password']) && !empty($conf['password'])) {
                $redis->auth($conf['password']);
            }

            $selectDb = (isset($conf['select']) && is_int($conf['select'])) ? $conf['select'] : self::REDIS_QUEUE_DATABASE;
            $redis->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);
            $redis->select($selectDb);

            $connInfo = [
                'first_connect_time' => $now,
                'redis' => $redis,
            ];

            $redisArr[$key] = $connInfo;
        }
        unset($key, $conf, $now, $socketTimeout, $lastTime, $maxTime, $pingRes, $redis);

        return $connInfo['redis'] ?? null;
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
     * 重置redis客户端连接
     * @param array $conf redis配置
     * @return $this
     */
    public function resetRedis(array $conf) {
        if(empty($conf)) $conf = $this->redisConf;
        $this->redis = self::getRedisClient($conf);
        return $this;
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
        $res = self::getRedisClient($this->redisConf)->hSetNx($allKey, $queueName, $queKey);
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
        $res = self::getRedisClient($this->redisConf)->hGetAll($allKey);
        return empty($res) ? [] : $res;
    }


    /**
     * 统计所有队列
     * @return int
     */
    public function countAllQueueNames() {
        $allKey = self::getHashTableKey();
        $res = (int)self::getRedisClient($this->redisConf)->hLen($allKey);
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
            $res = self::getRedisClient($this->redisConf)->hExists($allKey, $queueName);
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
            $queueKey = self::getRedisClient($this->redisConf)->hGet($allKey, $queueName);
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
        $res = self::getRedisClient($this->redisConf)->hDel($allKey, $baseInfo->queueKey);
        if($res) {
            unset(self::$allQuekeys[$queueName]);
            self::getRedisClient($this->redisConf)->del($baseInfo->queueKey);
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
            $res = (int)self::getRedisClient($this->redisConf)->zCount($baseInfo->queueKey, PHP_INT_MIN, PHP_INT_MAX);
        }else{
            $res = (int)self::getRedisClient($this->redisConf)->lLen($baseInfo->queueKey);
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
            $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : microtime(true);
            $res = self::getRedisClient($this->redisConf)->zAdd($baseInfo->queueKey, $score, $item);
        }else{
            $res = self::getRedisClient($this->redisConf)->lPush($baseInfo->queueKey, $item);
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
        self::getRedisClient($this->redisConf)->multi();
        foreach ($items as $k=>$item) {
            if($baseInfo->isSort) {
                $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : microtime(true);
                self::getRedisClient($this->redisConf)->zAdd($baseInfo->queueKey, $score, $item);
            }else{
                self::getRedisClient($this->redisConf)->lPush($baseInfo->queueKey, $item);
            }
        }
        $mulRes = self::getRedisClient($this->redisConf)->exec();
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
            $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : microtime(true);
            $res = self::getRedisClient($this->redisConf)->zAdd($baseInfo->queueKey, $score, $item);
        }else{
            $res = self::getRedisClient($this->redisConf)->rPush($baseInfo->queueKey, $item);
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
        self::getRedisClient($this->redisConf)->multi();
        foreach ($items as $k=>$item) {
            if($baseInfo->isSort) {
                $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : microtime(true);
                self::getRedisClient($this->redisConf)->zAdd($baseInfo->queueKey, $score, $item);
            }else{
                self::getRedisClient($this->redisConf)->rPush($baseInfo->queueKey, $item);
            }
        }
        $mulRes = self::getRedisClient($this->redisConf)->exec();
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
            $res = self::getRedisClient($this->redisConf)->zRange($baseInfo->queueKey, 0, 0); //从小到大排
            if($res) $res = $res[0];
        }else{
            $res = self::getRedisClient($this->redisConf)->lPop($baseInfo->queueKey);
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
            $res = self::getRedisClient($this->redisConf)->zRevRange($baseInfo->queueKey, 0, 0); //从大到小排
            if($res) $res = $res[0];
        }else{
            $res = self::getRedisClient($this->redisConf)->rPop($baseInfo->queueKey);
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
        self::getRedisClient($this->redisConf)->multi();
        self::getRedisClient($this->redisConf)->zAdd($tranQueKey, $score, $tranItemKey);
        self::getRedisClient($this->redisConf)->hSet($tranTabKey, $tranItemKey, $tranItemData);
        if($baseInfo->isSort) {
            self::getRedisClient($this->redisConf)->zRem($baseInfo->queueKey, $item);
        }
        $tranRes = self::getRedisClient($this->redisConf)->exec();
        if(!isset($tranRes[0]) || empty($tranRes[0])) {
            $this->setError('消息加入中转失败');
            self::getRedisClient($this->redisConf)->hDel($tranTabKey, $tranItemKey);
            self::getRedisClient($this->redisConf)->zRem($tranQueKey, $tranItemKey);
            //重新入栈
            if(!$baseInfo->isSort) $this->push($item, $queueName);
        }else{
            $res = true;
        }

        return $res;
    }


    /**
     * 获取中转消息的哈希key
     * @param array $item 原消息
     * @param string $queueName 原队消息列名
     * @return string
     */
    public function getTranItemKey($item=[], $queueName='') {
        $res = '';
        if(empty($item)) return $res;

        if(empty($queueName)) $queueName = $this->curQueName;

        $tranItemData = [
            'queueName' => $queueName,
            'item' => $item,
        ];
        $res = md5(serialize($tranItemData));

        return $res;
    }


    /**
     * 获取多个中转消息的哈希key
     * @param array $items 原消息数组
     * @param string $queueName 原队消息列名
     * @return array
     */
    public function getMultTranItemKeys($items=[], $queueName='') {
        $res = [];
        if(empty($items)) return $res;

        if(empty($queueName)) $queueName = $this->curQueName;
        foreach ($items as $item) {
            $tranItemData = [
                'queueName' => $queueName,
                'item' => $item,
            ];
            $key = md5(serialize($tranItemData));
            array_push($res, $key);
        }

        return $res;
    }



    /**
     * 根据消息中转key获取消息
     * @param string $key
     * @return bool|string
     */
    public function getTranItemByKey($key='') {
        $res = false;
        if(empty($key)) return $res;

        $tranTabKey = self::getTransTableKey();
        $res = self::getRedisClient($this->redisConf)->hGet($tranTabKey, $key);

        return $res;
    }


    /**
     * 根据消息中转keys获取多个消息
     * @param array $keys
     * @return array
     */
    public function getMultTranItemByKeys($keys=[]) {
        $res = [];
        if(empty($keys)) return $res;

        $tranTabKey = self::getTransTableKey();
        $res = self::getRedisClient($this->redisConf)->hmGet($tranTabKey, $keys);

        return $res;
    }



    /**
     * 消息确认(处理完毕后向队列确认,成功则从中转队列移除;失败则重新加入任务队列;若无确认,消息重新入栈)
     * @param mixed $item 消息或该消息的中转key
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

        if(is_string($item)) {
            $tranItemKey = $item;
            if(!$procRes) $item = $this->getTranItemByKey($tranItemKey);
        }else{
            $tranItemKey = $this->getTranItemKey($item);
        }

        $tranQueKey = self::getTransQueueKey();
        $tranTabKey = self::getTransTableKey();

        //redis事务
        self::getRedisClient($this->redisConf)->multi();
        self::getRedisClient($this->redisConf)->zRem($tranQueKey, $tranItemKey);
        self::getRedisClient($this->redisConf)->hDel($tranTabKey, $tranItemKey);
        if(!$procRes) { //重新入栈
            if($baseInfo->isSort) {
                $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : microtime(true);
                $res = self::getRedisClient($this->redisConf)->zAdd($baseInfo->queueKey, $score, $item);
            }else{
                $res = self::getRedisClient($this->redisConf)->rPush($baseInfo->queueKey, $item);
            }
        }

        $cfmRes = self::getRedisClient($this->redisConf)->exec();
        if(!isset($cfmRes[0]) || empty($cfmRes[0])) {
            $this->setError('消息确认失败');
        }else{
            $res = true;
        }

        return $res;
    }


    /**
     * 消息批量确认(处理完毕后向队列确认,成功则从中转队列移除;失败则重新加入任务队列;若无确认,消息重新入栈)
     * @param array $items 消息数组
     * @param bool $procRes 处理结果:true成功,false失败
     * @param int $num 分批数量
     * @param string $queueName 队列名,为空则取当前队列名
     * @return int
     */
    public function confirmMult($items=[], $procRes=true, $num=50, $queueName='') {
        $res = $sucNum = $faiNum = 0;
        $index = $num * 2 - 1;
        $isKey = false;
        if(empty($items)) {
            $this->setError('消息不能为空');
            return $res;
        }

        if(empty($queueName)) $queueName = $this->curQueName;
        $baseInfo = $this->getQueueBaseInfo($queueName);
        if(empty($baseInfo)) {
            $this->setError('队列名不存在');
            return $res;
        }

        if(is_string(current($items))) {
            $keys = $items;
            $isKey = true;
        }else{
            $keys = $this->getMultTranItemKeys($items);
        }

        $tranQueKey = self::getTransQueueKey();
        $tranTabKey = self::getTransTableKey();

        //数组分段
        $slices = array_chunk($keys, $num, true);
        foreach ($slices as $slice) {
            if(!$procRes && $isKey) {
                $sliceItems = $this->getMultTranItemByKeys($slice);
            }

            //redis事务
            self::getRedisClient($this->redisConf)->multi();

            foreach ($slice as $k=>$tranItemKey) {
                self::getRedisClient($this->redisConf)->zRem($tranQueKey, $tranItemKey);
                self::getRedisClient($this->redisConf)->hDel($tranTabKey, $tranItemKey);

                if(!$procRes) { //重新入栈
                    $item = $isKey ? $sliceItems[$tranItemKey] : $items[$k];
                    if($baseInfo->isSort) {
                        $score = isset($item[self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$item[self::REDIS_QUEUE_SCORE_FIELD] : microtime(true);
                        $res = self::getRedisClient($this->redisConf)->zAdd($baseInfo->queueKey, $score, $item);
                    }else{
                        $res = self::getRedisClient($this->redisConf)->rPush($baseInfo->queueKey, $item);
                    }
                }

            }

            $cfmRes = self::getRedisClient($this->redisConf)->exec();
            $slicNum = count($slice);
            if(!isset($cfmRes[$index]) || empty($cfmRes[$index])) {
                $faiNum += $slicNum;
            }else{
                $sucNum += $slicNum;
            }
        }

        return $sucNum;
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

        self::getRedisClient($this->redisConf)->del($baseInfo->queueKey);

        return true;
    }


    /**
     * 获取中转队列执行锁
     * @param string $serverUniqueId 服务器唯一标识
     * @return bool
     */
    public function getTransQueueLock($serverUniqueId='') {
        $key = self::getTransLockKey($serverUniqueId);
        $now = time();

        $res = self::getRedisClient($this->redisConf)->setnx($key, $now);
        if($res) {
            self::getRedisClient($this->redisConf)->expire($key, self::REDIS_QUEUE_TRANS_LOCKTIM);
        }

        return $res;
    }


    /**
     * 解锁中转队列执行
     * @param string $serverUniqueId 服务器唯一标识
     * @return int
     */
    public function unlockTransQueue($serverUniqueId='') {
        $key = self::getTransLockKey($serverUniqueId);
        $res = self::getRedisClient($this->redisConf)->del($key);
        return $res;
    }


    /**
     * 获取单个消息的处理锁
     * @param array $item 消息
     * @param int $lockTime 锁时间,秒
     * @param string $queueName 队列名
     * @return bool
     */
    public function getItemProcessLock($item=[], $lockTime=10, $queueName='') {
        $res = true;
        if(empty($queueName)) $queueName = $this->curQueName;
        $key = self::getItemProcessKey($item, $queueName);
        $now = time();
        $data = $now + $lockTime;

        if($ret = self::getRedisClient($this->redisConf)->setnx($key, $data)) {
            self::getRedisClient($this->redisConf)->expire($key, $lockTime);
        }else{
            $val = self::getRedisClient($this->redisConf)->get($key);
            if(is_numeric($val) && $val> $now) {
                $res = false;
            }else{
                self::getRedisClient($this->redisConf)->set($key, $data, $lockTime);
            }
        }

        return $res;
    }


    /**
     * 解锁单个消息的处理
     * @param array $item 消息
     * @param string $queueName 队列名
     * @return int
     */
    public function unlockItemProcess($item=[], $queueName='') {
        if(empty($queueName)) $queueName = $this->curQueName;
        $key = self::getItemProcessKey($item, $queueName);
        $res = self::getRedisClient($this->redisConf)->del($key);
        return $res;
    }


    /**
     * 循环检查中转队列,重新入栈
     * @param string $serverUniqueId 服务器唯一标识
     * @return bool|int
     */
    public function loopTransQueue($serverUniqueId='') {
        $res = false;
        $lock = $this->getTransQueueLock($serverUniqueId);
        if(!$lock) {
            $this->setError('已有其他进程正执行中转队列');
            return $res;
        }

        $tranQueKey = self::getTransQueueKey();
        $tranTabKey = self::getTransTableKey();
        $len = (int)self::getRedisClient($this->redisConf)->hLen($tranTabKey);
        if($len<=0) {
            $this->setError('中转队列为空');
            $this->unlockTransQueue($serverUniqueId);
            return $res;
        }

        $beginTime = time();
        $expire = ($this->transTime>0) ? intval($this->transTime) : self::REDIS_QUEUE_TRANS_TIME;
        set_time_limit(self::REDIS_QUEUE_TRANS_LOCKTIM);
        $successNum = 0;

        try{
            while ($list = self::getRedisClient($this->redisConf)->zRange($tranQueKey, 0, 0, true)) {
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

                $tranItem = self::getRedisClient($this->redisConf)->hGet($tranTabKey, $itemKey);
                if(empty($tranItem)) {
                    self::getRedisClient($this->redisConf)->zRem($tranQueKey, $itemKey);
                    $this->setError('消息不存在');
                    continue;
                }

                if(($now - $time) > $expire) {
                    //检查原队列是否存在
                    $oldQueInfo = $this->getQueueBaseInfo($tranItem['queueName']);
                    if($oldQueInfo) { //超时的重新入栈
                        self::getRedisClient($this->redisConf)->multi();
                        self::getRedisClient($this->redisConf)->hDel($tranTabKey, $itemKey);
                        self::getRedisClient($this->redisConf)->zRem($tranQueKey, $itemKey);
                        if($oldQueInfo->isSort) {
                            $score = isset($tranItem['item'][self::REDIS_QUEUE_SCORE_FIELD]) ? (float)$tranItem['item'][self::REDIS_QUEUE_SCORE_FIELD] : microtime(true);
                            self::getRedisClient($this->redisConf)->zAdd($oldQueInfo->queueKey, $score, $tranItem['item']);
                        }else{
                            self::getRedisClient($this->redisConf)->rPush($oldQueInfo->queueKey, $tranItem['item']);
                        }
                        $addRes = self::getRedisClient($this->redisConf)->exec();
                        if(!isset($addRes[0]) || empty($addRes[0])) {
                            $this->setError('消息重新入栈失败');
                        }else{
                            $successNum++;
                        }
                    }else{ //丢弃
                        $this->setError('消息原队列不存在');
                        self::getRedisClient($this->redisConf)->hDel($tranTabKey, $itemKey);
                        self::getRedisClient($this->redisConf)->zRem($tranQueKey, $itemKey);
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
        $this->unlockTransQueue($serverUniqueId);

        return (int)$successNum;
    }


}