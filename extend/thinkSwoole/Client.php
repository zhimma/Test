<?php

namespace thinkSwoole;

class Client
{
    protected static $client;
    protected $container;

    /**
     * Client constructor.
     */
    public function __construct()
    {
        self::$client = new \swoole_client(SWOOLE_SOCK_TCP);
        self::$client->connect("127.0.0.1", 9501);
    }

    /**
     * 通知服务器处理数据
     * @param $class
     * @param $fileName
     *
     * @author mma5694@gmail.com
     * @date 2018年3月23日14:26:08
     */
    public function sendTo($class, $fileName)
    {
        $data = ['class' => $class, 'data' => $fileName];

        self::$client->send(json_encode($data));
    }
}