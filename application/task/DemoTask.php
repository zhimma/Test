<?php
/**
 * Created by PhpStorm.
 * User: mma
 * Date: 2018/3/23
 * Time: 10:20
 */
namespace app\task;

use think\Db;

class DemoTask
{
    protected $server;
    protected $data;

    public function __construct(\swoole_server $server,$data)
    {
        $this->server = $server;
        $this->data = $data;
    }

    public function handle()
    {
        echo "handle接收任务" .PHP_EOL;
        if($this->cacheUserData($this->data)){
           $this->userDataStore();
       }
    }
    /**
     * 新文件上传后  数据写入redis 队列
     *
     * @param string $fileName
     *
     * @return bool
     *
     * @author mma5694@gmail.com
     * @date
     */
    protected function cacheUserData($fileName = '')
    {
        echo "cache 用户信息" .PHP_EOL;
        $fileInfo = explode('_', $fileName);
        $userId = $fileInfo[0];
        $filePath = $fileInfo[1];
        // 读取文件
        $excelData = file($filePath);
        $chunkData = array_chunk($excelData, 5000);
        $count = count($chunkData);
        for ($i = 0; $i < $count; $i++) {
            foreach ($chunkData[$i] as $value) {
                $encode = mb_detect_encoding($value, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']);
                $string = mb_convert_encoding(trim(strip_tags($value)), 'UTF-8', $encode);
                $this->server->redis->lpush('user_info' . '_' . $userId, serialize(explode(',', $string)));
            }
        }
        // 将本用户的id 和 数据队列 存入 set中
        $this->server->redis->hset('list_user_relation', $userId, 'user_info' . '_' . $userId);

        return true;
    }

    /**
     * 缓存数据入库
     *
     * @author 马雄飞 <mma5694@gmail.com>
     * @date   2018年03月10日15:57:28
     */
    public function userDataStore()
    {
        echo "save 用户信息 进入mysql" .PHP_EOL;
        // 从hash中读取文件
        $dataList = $this->server->redis->hgetall('list_user_relation');
        foreach ($dataList as $key => $value) {
            $userData = $this->server->redis->lrange($value, 0, -1);
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
                // 保存数据库
                $insertCount = Db::name('users')->insertAll($insertData);
                $this->server->redis->hset('save_mysql_result', $key . '_' . $i, $insertCount);
                $this->server->redis->hincrby('save_mysql_result_count', $key, intval($insertCount));
            }
            // 删除队列
            $this->server->redis->del($value);
            // 删除list_user_relation
            $this->server->redis->hdel('list_user_relation', $key);

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
        if (!$this->server->redis->sadd('user_phone', $userData[2])
            || !isset($userData[2]) || empty($userData[2])
            || !$this->validatePhone($userData[2])) {
            //存入hash表 值存在 将覆盖
            $this->server->redis->hset('has_something_wrong_user', $userId . '_' . $userData[0], serialize($userData));
            $this->server->redis->hincrby('has_something_wrong_user_count', $userId, 1);

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
}