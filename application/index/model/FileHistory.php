<?php

namespace app\index\model;

use think\Model;
use think\Session;

class FileHistory extends Model
{
    /**
     * 判断文件是否存在  是否保存
     * @param $fileName
     *
     * @return bool
     * @throws \think\exception\DbException
     *
     * @author 马雄飞 <xiongfei.ma@pactera.com>
     * @date 2018-03-11 13:52:43
     */
    public function fileExist($fileName)
    {
        $res = 0;
        $data = $this::get(['value' => $fileName]);
        if (is_null($data)) {
            $res = $this::save(['value' => $fileName, 'user_id' => Session::get('user_id'), 'create_time' => time()]);
        }
        if ($res) {
            return true;
        } else {
            return false;
        }

    }
}