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
            "log_file" => "../runtime/log/taskqueueu.log",//日志
        ));
        //$server->start启动服务器
        $this->server->start();
    }

    public function onManagerStart()
    {
        echo "manage start" . PHP_EOL;
    }

    public function onWorkerStart($server,$workerId)
    {
//        echo "worker {$workerId} start" .PHP_EOL;
    }

    public function onWorkerStop($server , $workerId)
    {
        echo "worker {$workerId} stop" .PHP_EOL;
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
      $filePath = $data;


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

}

$server = new Server();