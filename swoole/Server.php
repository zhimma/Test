<?php

class Server
{
    private $server;

    public function __construct()
    {
        //new swoole_server
        $this->server = new \swoole_server("0.0.0.0", 9501);
        //$server->on 设置事件回调
        $event = ['Connect', 'Receive', 'Task', 'Finish', 'Close', 'ManagerStart', 'WorkerStart', 'WorkerStop'];
        foreach ($event as $value) {
            $this->server->on($value, array($this, 'on' . $value));
        }

        //$server->set 设置运行参数
        $this->server->set(array(
            'worker_num'      => 1,   //一般设置为服务器CPU数的1-4倍
            'daemonize'       => false,  //以守护进程执行
            'max_request'     => 10000,
            'dispatch_mode'   => 2,
            'task_worker_num' => 8,  //task进程的数量
            "task_ipc_mode "  => 3,  //使用消息队列通信，并设置为争抢模式
            "log_file"        => "../runtime/log/taskqueueu.log",//日志
        ));
        //$server->start启动服务器
        $this->server->start();
    }

    public function onManagerStart()
    {
        echo "manage start" . PHP_EOL;
    }

    public function onWorkerStart($server, $workerId)
    {
        $redis = new redis;
        $redis->connect('127.0.0.1', 6379);
        $server->redis = $redis;
//        echo "worker {$workerId} start" .PHP_EOL;
    }

    public function onWorkerStop($server, $workerId)
    {
        echo "worker {$workerId} stop" . PHP_EOL;
    }

    /**
     * 当有新的连接进入  在worker 进程中回调，而不是主进程
     *
     * @param $server
     * @param $fd
     *
     * @author mma5694@gmail.com
     * @date
     */
    public function onConnect($server, $fd)
    {
        echo "client {$fd} is connect" . PHP_EOL;
    }

    /**
     * 接收到数据时回调此函数  发生在worker进程中
     *
     * @param swoole_server $server     swoole_server对象
     * @param               $fd         tcp客户端链接的唯一标识符
     * @param               $from_id    tcp连接所在的Reactor线程ID
     * @param               $data       收到的数据内容
     *
     * @author mma5694@gmail.com
     * @date
     */
    public function onReceive(swoole_server $server, $fd, $from_id, $data)
    {
        $server->task($data);
        /*fwrite(STDOUT, "请回复消息：");
        $msg = trim(fgets(STDIN));
        $this->server->send($fd,$msg,$from_id);*/
    }

    public function onTask($server, $task_id, $from_id, $data)
    {
        if ($this->cacheUserData($server, $data)) {
            $this->userDataStore($server);
        }
    }

    public function onFinish($server, $task_id, $data)
    {
        echo "Task {$task_id} finish" . PHP_EOL;
        //echo "Result: {$data}n";
    }

    public function onClose($server, $task_id)
    {
        echo "Task {$task_id} close" . PHP_EOL;
        //echo "Result: {$data}n";
    }

    /**
     * 新文件上传后  数据写入redis 队列
     *
     * @param        $server
     * @param string $fileName
     *
     * @return bool
     *
     * @author mma5694@gmail.com
     * @date
     */
    protected function cacheUserData($server, $fileName = '')
    {
        $fileInfo = explode('_', $fileName);
        $userId = $fileInfo[0];
        $filePath = $fileInfo[1];
        // 读取文件
        $excelData = file($filePath);
        $chunkData = array_chunk($excelData, 5000);
        $count = count($chunkData);
        for ($i = 0; $i < $count; $i++) {
            foreach ($chunkData[$i] as $value) {
                $encode = mb_detect_encoding($value, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']);
                $string = mb_convert_encoding(trim(strip_tags($value)), 'UTF-8', $encode);
                $server->redis->lpush('user_info' . '_' . $userId, serialize(explode(',', $string)));
            }
        }
        $server->redis->flushdb();

        // 将本用户的id 和 数据队列 存入 set中
        $server->redis->hset('list_user_relation', $userId, 'user_info' . '_' . $userId);

        return true;
    }

    /**
     * 缓存数据入库
     *
     * @author 马雄飞 <mma5694@gmail.com>
     * @date   2018年03月10日15:57:28
     */
    public function userDataStore($server)
    {

        // 从hash中读取文件
        $dataList = $server->redis->hgetall('list_user_relation');
        foreach ($dataList as $key => $value) {
            $userData = $server->redis->lrange($value, 0, -1);
            // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
            $chunkData = array_chunk($userData, 5000);
            $count = count($chunkData);
            for ($i = 0; $i < $count; $i++) {
                $insertData = [];
                foreach ($chunkData[$i] as $val) {
                    if (!empty($val)) {
                        $user = $this->validateAndSave(unserialize($val), $key);
                        if (!empty($user)) {
                            array_push($insertData, $user);
                        }
                    }
                }
                // 保存数据库
//                $insertCount = Db::name('users')->insertAll($insertData);
                $server->redis->hset('save_mysql_result', $key . '_' . $i, $insertCount);
                $server->redis->hincrby('save_mysql_result_count', $key, intval($insertCount));
            }
            // 删除队列
            $server->redis->del($value);
            // 删除list_user_relation
            $server->redis->hdel('list_user_relation', $key);

        }

    }

    /**
     * 验证数据  返回合格的可入库数据
     *
     * @param $userData
     * @param $userId
     *
     * @return array
     *
     * @author 马雄飞 <mma5694@gmail.com>
     * @date   2018-03-11 13:37:49
     */
    protected function validateAndSave($server, $userData, $userId)
    {
        //去除csv多余引号
        $userData = array_map(function ($value) {
            return str_replace('"', '', $value);
        }, $userData);
        //不能写入集合
        if (!$server->redis->sadd('user_phone', $userData[2])
            || !isset($userData[2]) || empty($userData[2])
            || !$this->validatePhone($userData[2])) {
            //存入hash表 值存在 将覆盖
            $server->redis->hset('has_something_wrong_user', $userId . '_' . $userData[0], serialize($userData));
            $server->redis->hincrby('has_something_wrong_user_count', $userId, 1);

            return [];
        }

        return [
            'name'        => $userData[1],
            'phone'       => $userData[2],
            'sex'         => intval($userData[3]),
            'create_time' => intval($userData[4]),
            'update_time' => intval($userData[5])
        ];
    }

    /**
     * 正则验证手机号
     *
     * @param $phone
     *
     * @return false|int
     *
     * @author 马雄飞 <mma5694@gmail.com>
     * @date   2018年03月11日18:18:19
     */
    public function validatePhone($phone)
    {
        return preg_match("/^1[34578]{1}\d{9}$/", $phone);
    }

}

$server = new Server();