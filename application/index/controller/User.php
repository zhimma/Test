<?php

namespace app\index\controller;

use think\Db;
use think\Session;

class User extends Base
{
    /**
     * 用户列表
     *
     * @param int $p
     *
     * @return \think\response\Json
     *
     * @author mma5694@gmail.com
     * @date   2018年3月12日10:36:21
     */
    public function index($p = 1)
    {
        $data = Db::table('users')->page($p, 10)->select();

        return json(['status' => 1, 'data' => $data]);
    }

    /**
     * 用户上传详情
     *
     * @return \think\response\Json
     *
     * @author mma5694@gmail.com
     * @date   2018-3-12 10:36:40
     */
    public function getUploadDetail()
    {
        $user_id = Session::get('user_id');
        $successCount = self::$redis->hget('save_mysql_result_count', $user_id);
        $errorCount = self::$redis->hget('has_something_wrong_user_count', $user_id);

        return json(['status' => 1, 'data' => ['success' => $successCount, 'error' => $errorCount]]);
    }

    /**
     * 新文件上传后  数据写入redis 队列
     *
     * @param $fileName
     *
     * @author 马雄飞 <mma5694@gmail.com>
     * @date   2018年03月10日15:55:31
     */
    public function handleUserFileBak($fileName = '/data/www/Test/public/uploads/20180309/gsu_users.csv')
    {
        $fileNameArray = explode('/', $fileName);
        $prefixName = explode('.', array_pop($fileNameArray));
        $filePath = implode('/', $fileNameArray);
        shell_exec('cd ' . $filePath);
        shell_exec('gsplit -a 3 -d -l 200000 ' . $fileName . ' ' . $prefixName[0] . '_');
        $fileLength = shell_exec('ls -l | grep " ' . $prefixName[0] . '_*"|wc -l');
        for ($i = 0; $i < $fileLength; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die("could not fork");
            } elseif ($pid) {
            } else {// 子进程处理
                $excelData = file($filePath . '/' . $prefixName[0] . '_00' . $i . '.' . $prefixName[1]);
                $chunkData = array_chunk($excelData, 5000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
                $count = count($chunkData);
                for ($i = 0; $i < $count; $i++) {
                    foreach ($chunkData[$i] as $value) {
                        $encode = mb_detect_encoding($value, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']);
                        $string = mb_convert_encoding(trim(strip_tags($value)), 'UTF-8', $encode);
                        self::$redis->lpush('userInfo', serialize(explode(',', $string)));
                    }
                }
            }
        }
        while (pcntl_waitpid(0, $status) != -1) {
            $status = pcntl_wexitstatus($status);
        }
        exit;
    }
}
