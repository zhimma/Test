<?php
namespace app\index\controller;

class Index extends Base
{
    public function index()
    {
        for ($i = 1;$i<10;$i++){
            self::$redis->lpush('filePath',$i);
        }
        if (self::$redis->llen('filePath') != 0) {
            $data = self::$redis->lrange('filePath',0,-1);
            foreach ($data as $key => $value){
                echo self::$redis->lpop('filePath');
            }
        }
        return view('index');
    }

    /**
     * 文件上传处理
     * @return string
     *
     * @author mma5694@gmail.com
     * @date 2018年3月9日17:08:29
     */
    public function upload()
    {
        $file = request()->file('file');
        $fileMd5 = md5_file($file->getPathname());
        //查询数据库  判断文件是否上传过
       /* if($fileMd5 == 'e221f1dd892baf7861ea8babb956e14b'){
            return '此文件已经上传';
        }*/
        // 移动到框架应用根目录/public/uploads/ 目录下
        if($file){
            $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
            if($info){
              self::$redis->lpush('filePath',$info->getPathname());
            }else{
                // 上传失败获取错误信息
                echo $file->getError();
            }
        }
    }

    public function test()
    {
        echo "it's work";
    }
}
