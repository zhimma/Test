<?php
/**
 * Created by PhpStorm.
 * User: mma
 * Date: 2018/3/23
 * Time: 11:49
 */

namespace thinkSwoole;

use think\console\Command;
use think\console\Input;
use think\console\Output;

class StartServer extends Command
{
    /**
     * 查看手册
     *
     * @author mma5694@gmail.com
     * @date 2018-3-23 14:25:37
     */
    protected function configure()
    {
        $this->setName('swoole')->setDescription('start swoole server');
    }

    protected function execute(Input $input, Output $output)
    {
        $issServer= new Server();
        if($issServer){
            $output->writeln('服务端启动成功!');
        }else{
            $output->writeln('Sorry,服务端启动失败!');
        }
    }
}