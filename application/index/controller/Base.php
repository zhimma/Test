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
    /**
     * 初始化
     * Base constructor.
     */
    public function __construct()
    {
    }
}
