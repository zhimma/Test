<?php

namespace app\index\controller;

use app\task\DemoTask;
use think\Exception;
use think\Session;
use thinkSwoole\Client;

class Index extends Base
{
    /**
     * 上传页面
     *
     * @return \think\response\View
     *
     * @author 马雄飞 <xiongfei.ma@pactera.com>
     * @date   2018-03-11 13:39:06
     */
    public function index()
    {
        self::$redis->flushdb();
        return view('index');
    }

    /**
     * 文件上传处理
     * @param Client $client
     *
     * @return \think\response\Json
     *
     * @author mma5694@gmail.com
     * @date 2018年3月23日14:28:07
     */
    public function upload(Client $client)
    {
        try {
            $file = request()->file('file');
            if (!$file) {
                throw new Exception('上传失败');
            }
            // 判断文件是否曾上传过
            /*$model = new FileHistory();
            $res = $model->fileExist(md5_file($file->getPathname()));
            if (!$res) {
                throw new Exception('错误，此文件数据已保存');
            }*/
            // 移动到框架应用根目录/public/uploads/ 目录下
            // 文件是否保存成功
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if (!$info) {
                throw new Exception($file->getError());
            }
            $fileName = Session::get('user_id') . '_' . $info->getPathname();
            $client->sendTo(DemoTask::class,$fileName);
            return json(['status' => 1, 'msg' => '上传成功，请稍后查询结果']);

        } catch (Exception $e) {
            return json(['status' => 0, 'msg' => $e->getMessage()]);
        }
    }
}
