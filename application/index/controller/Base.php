<?php

namespace app\index\controller;

use Predis\Client;
use think\Controller;

class Base extends Controller
{
    protected static $redis = null;

    /**
     * Base constructor.
     */
    public function __construct()
    {
        if (is_null(self::$redis)) {
            $server = array(
                'host'     => '127.0.0.1',
                'port'     => 6379,
                'database' => 0
            );
            self::$redis = new Client ($server);
        } else {
            return self::$redis;
        }
    }
}
