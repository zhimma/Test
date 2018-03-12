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
     * 监测¬文件上传队列
     *
     * @author mma5694@gmail.com
     * @date   2018年3月9日18:57:20
     */
    public function parseUserFile()
    {
        //判断队列中是否有新文件
        if (self::$redis->llen('file_path') != 0) {
            //获取所有文件路径
            $fileNames = self::$redis->lrange('file_path', 0, -1);
            foreach ($fileNames as $key => $value) {
                $this->cacheUserData($value);
            }
        }
    }

    /**
     * 新文件上传后  数据写入redis 队列
     *
     * @param string $fileName
     *
     * @author mma5694@gmail.com
     * @date   2018年3月12日11:18:28
     */
    protected function cacheUserData($fileName = '')
    {
        $fileInfo = explode('_', $fileName);
        $userId = $fileInfo[0];
        $filePath = $fileInfo[1];
        //读取文件
        $excelData = file($filePath);
        $chunkData = array_chunk($excelData, 5000);
        $count = count($chunkData);
        for ($i = 0; $i < $count; $i++) {
            foreach ($chunkData[$i] as $value) {
                $encode = mb_detect_encoding($value, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']);
                $string = mb_convert_encoding(trim(strip_tags($value)), 'UTF-8', $encode);
                self::$redis->lpush('user_info' . '_' . $userId, serialize(explode(',', $string)));
            }
        }
        //将本用户的id 和 数据队列 存入 set中
        self::$redis->hset('list_user_relation', $userId, 'user_info' . '_' . $userId);
        //删除文件路径
        self::$redis->lrem('file_path', 1, $fileName);
    }

    /**
     * 缓存数据入库
     *
     * @author 马雄飞 <mma5694@gmail.com>
     * @date   2018年03月10日15:57:28
     */
    public function userDataStore()
    {

        //从hash中读取文件
        $dataList = self::$redis->hgetall('list_user_relation');
        foreach ($dataList as $key => $value) {
            $userData = self::$redis->lrange($value, 0, -1);
            // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
            $chunkData = array_chunk($userData, 5000);
            $count = count($chunkData);
            for ($i = 0; $i < $count; $i++) {
                $insertData = [];
                foreach ($chunkData[$i] as $val) {
                    if (!empty($val)) {
                        $user = $this->validateAndSave(unserialize($val), $key);
                        if (!empty($user)) {
                            array_push($insertData, $user);
                        }
                    }
                }
                //保存数据库
                $insertCount = Db::name('users')->insertAll($insertData);
                self::$redis->hset('save_mysql_result', $key . '_' . $i, $insertCount);
                self::$redis->hincrby('save_mysql_result_count', $key, $insertCount);
            }
            //删除队列
            self::$redis->del($value);
            //删除list_user_relation
            self::$redis->hdel('list_user_relation', $key);

        }
    }

    /**
     * 验证数据  返回合格的可入库数据
     *
     * @param $userData
     * @param $userId
     *
     * @return array
     *
     * @author 马雄飞 <mma5694@gmail.com>
     * @date   2018-03-11 13:37:49
     */
    protected function validateAndSave($userData, $userId)
    {
        //去除csv多余引号
        $userData = array_map(function ($value) {
            return str_replace('"', '', $value);
        }, $userData);
        //不能写入集合
        if (!self::$redis->sadd('user_phone', $userData[2]) || !isset($userData[2]) || empty($userData[2]) || !$this->validatePhone($userData[2])) {
            //存入hash表 值存在 将覆盖
            self::$redis->hset('has_something_wrong_user', $userId . '_' . $userData[0], serialize($userData));
            self::$redis->hincrby('has_something_wrong_user_count', $userId, 1);

            return [];
        }

        return [
            'name'        => $userData[1],
            'phone'       => $userData[2],
            'sex'         => intval($userData[3]),
            'create_time' => intval($userData[4]),
            'update_time' => intval($userData[5])
        ];

    }

    /**
     * 正则验证手机号
     *
     * @param $phone
     *
     * @return false|int
     *
     * @author 马雄飞 <mma5694@gmail.com>
     * @date   2018年03月11日18:18:19
     */
    public function validatePhone($phone)
    {
        return preg_match("/^1[34578]{1}\d{9}$/", $phone);
    }

    /**
     * 新文件上传后  数据写入redis 队列
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
