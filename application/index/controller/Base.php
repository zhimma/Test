<?php

namespace app\index\controller;

use Predis\Client;
use think\Controller;
use think\Session;

/**
 * Class Base
 * 基类
 *
 * @package app\index\controller
 */
class Base extends Controller
{
    protected static $redis = null;

    /**
     * 初始化
     * Base constructor.
     */
    public function __construct()
    {
        Session::set('user_id', 2);
        if (is_null(self::$redis)) {
            $server = array(
                'host'     => '127.0.0.1',
                'port'     => 6379,
                'database' => 0
            );
            self::$redis = new Client($server);
        } else {
            return self::$redis;
        }


    }
}
