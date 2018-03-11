<?php

namespace app\index\controller;

use app\index\model\FileHistory;
use think\Session;

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

        /*$fileName = '/data/www/Test/public/user.csv';
        shell_exec('gsplit -a 3 -d -l 20000 ' . $fileName . ' '  . 'user_');*/

        return view('index');
    }

    /**
     * 文件上传处理
     *
     * @throws \think\exception\DbException
     *
     * @author 马雄飞 <xiongfei.ma@pactera.com>
     * @date   2018-03-11 13:58:14
     */
    public function upload()
    {
        $return = ['status' => 0, 'msg' => '上传失败'];
        $file = request()->file('file');
        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $model = new FileHistory();
            $res = $model->fileExist(md5_file($file->getPathname()));
            if ($res) {
                $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
                if ($info) {
                    self::$redis->lpush('file_path', Session::get('user_id') . '_' . $info->getPathname());
                    $return['status'] = 1;
                    $return['msg'] = '上传成功，请稍后查询结果';
                } else {
                    // 上传失败获取错误信息
                    $return['msg'] = $file->getError();
                }
            } else {
                $return['msg'] = '错误，此文件数据已保存';
            }
        }

        return json($return);
    }
}
